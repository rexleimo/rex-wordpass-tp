"""Capture authenticated WordPress admin screens from the visible Chrome window."""

import ctypes
from ctypes import wintypes
import sys
import time
from pathlib import Path

from PIL import ImageGrab


USER32 = ctypes.windll.user32
KERNEL32 = ctypes.windll.kernel32
KERNEL32.GlobalAlloc.argtypes = (wintypes.UINT, ctypes.c_size_t)
KERNEL32.GlobalAlloc.restype = wintypes.HGLOBAL
KERNEL32.GlobalLock.argtypes = (wintypes.HGLOBAL,)
KERNEL32.GlobalLock.restype = ctypes.c_void_p
KERNEL32.GlobalUnlock.argtypes = (wintypes.HGLOBAL,)
KERNEL32.GlobalFree.argtypes = (wintypes.HGLOBAL,)
USER32.OpenClipboard.argtypes = (wintypes.HWND,)
USER32.OpenClipboard.restype = wintypes.BOOL
USER32.SetClipboardData.argtypes = (wintypes.UINT, wintypes.HANDLE)
USER32.SetClipboardData.restype = wintypes.HANDLE
SW_RESTORE = 9
VK_CONTROL = 0x11
VK_MENU = 0x12
VK_L = 0x4C
VK_V = 0x56
VK_A = 0x41
VK_ESCAPE = 0x1B
VK_RETURN = 0x0D
KEYEVENTF_KEYUP = 0x0002


def find_dashboard_window():
    matches = []

    @ctypes.WINFUNCTYPE(ctypes.c_bool, ctypes.c_void_p, ctypes.c_void_p)
    def callback(hwnd, _):
        if not USER32.IsWindowVisible(hwnd):
            return True
        length = USER32.GetWindowTextLengthW(hwnd)
        if not length:
            return True
        title = ctypes.create_unicode_buffer(length + 1)
        USER32.GetWindowTextW(hwnd, title, length + 1)
        if "toKraft" in title.value and "WordPress" in title.value:
            process_id = wintypes.DWORD()
            USER32.GetWindowThreadProcessId(hwnd, ctypes.byref(process_id))
            matches.append((hwnd, title.value, process_id.value))
        return True

    USER32.EnumWindows(callback, 0)
    if not matches:
        raise RuntimeError("Could not find the logged-in WordPress Chrome window.")
    hwnd, _title, _process_id = matches[0]
    return hwnd


def activate(hwnd):
    # Dismiss any transient app overlay that may be above the browser window.
    key_down(VK_ESCAPE)
    key_up(VK_ESCAPE)
    USER32.ShowWindow(hwnd, SW_RESTORE)
    current_thread = KERNEL32.GetCurrentThreadId()
    foreground_thread = USER32.GetWindowThreadProcessId(USER32.GetForegroundWindow(), None)
    target_thread = USER32.GetWindowThreadProcessId(hwnd, None)
    USER32.AttachThreadInput(current_thread, foreground_thread, True)
    USER32.AttachThreadInput(current_thread, target_thread, True)
    try:
        # A synthetic Alt keystroke grants this helper foreground permission.
        key_down(VK_MENU)
        key_up(VK_MENU)
        USER32.BringWindowToTop(hwnd)
        USER32.SetForegroundWindow(hwnd)
        USER32.SetFocus(hwnd)
    finally:
        USER32.AttachThreadInput(current_thread, target_thread, False)
        USER32.AttachThreadInput(current_thread, foreground_thread, False)
    for _ in range(10):
        if USER32.GetForegroundWindow() == hwnd:
            return
        time.sleep(0.1)
    raise RuntimeError("Could not bring the WordPress Chrome window to the foreground.")


def key_down(vk):
    USER32.keybd_event(vk, 0, 0, 0)


def key_up(vk):
    USER32.keybd_event(vk, 0, KEYEVENTF_KEYUP, 0)


def navigate(hwnd, url):
    rect = wintypes.RECT()
    USER32.GetWindowRect(hwnd, ctypes.byref(rect))
    # Use the visible address bar instead of a global Ctrl+L shortcut.
    USER32.SetCursorPos(rect.left + 500, rect.top + 100)
    USER32.mouse_event(0x0002, 0, 0, 0, 0)  # Left button down.
    USER32.mouse_event(0x0004, 0, 0, 0, 0)  # Left button up.
    time.sleep(0.2)
    key_down(VK_CONTROL)
    key_down(VK_A)
    key_up(VK_A)
    key_up(VK_CONTROL)
    time.sleep(0.2)
    clipboard_set(url)
    key_down(VK_CONTROL)
    key_down(VK_V)
    key_up(VK_V)
    key_up(VK_CONTROL)
    key_down(VK_RETURN)
    key_up(VK_RETURN)
    time.sleep(4.0)


def screenshot(hwnd, path):
    rect = wintypes.RECT()
    USER32.GetWindowRect(hwnd, ctypes.byref(rect))
    image = ImageGrab.grab(bbox=(rect.left, rect.top, rect.right, rect.bottom), all_screens=True)
    image.save(path)


def clipboard_set(text):
    payload = ctypes.create_unicode_buffer(text)
    size = ctypes.sizeof(payload)
    handle = KERNEL32.GlobalAlloc(0x0002, size)  # GMEM_MOVEABLE
    if not handle:
        raise ctypes.WinError()
    pointer = KERNEL32.GlobalLock(handle)
    ctypes.memmove(pointer, payload, size)
    KERNEL32.GlobalUnlock(handle)
    if not USER32.OpenClipboard(None):
        raise ctypes.WinError()
    try:
        USER32.EmptyClipboard()
        if not USER32.SetClipboardData(13, handle):  # CF_UNICODETEXT
            raise ctypes.WinError()
        handle = None  # Clipboard owns the allocation after SetClipboardData.
    finally:
        USER32.CloseClipboard()
        if handle:
            KERNEL32.GlobalFree(handle)


def main():
    if len(sys.argv) != 3:
        raise SystemExit("Usage: capture_admin.py <relative-admin-url> <output-png>")
    rel_url = sys.argv[1]
    output = Path(sys.argv[2]).resolve()
    output.parent.mkdir(parents=True, exist_ok=True)
    hwnd = find_dashboard_window()
    activate(hwnd)
    navigate(hwnd, "http://localhost:8080" + rel_url)
    screenshot(hwnd, output)
    print(output)


if __name__ == "__main__":
    main()
