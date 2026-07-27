# Studio International Newsroom

The source of truth is `pen/wp2.pen`, especially the desktop, 1024, 768, and mobile frames plus the interaction handoff board.

## Visual system

- Paper `#F2F0E9`, sand `#E8E4D8`, ink `#1B1B1B`, flame `#FF4D00`.
- Archivo Black for display typography and JetBrains Mono for interface/body text.
- Structural 2px rules, squared edges, no decorative shadows, and asymmetric editorial grids.
- Local vector artwork is used for editorial imagery so the theme remains self-contained.
- The home page uses controlled editorial density: one dominant lead, a live desk, ranked reading, recurring columns, topic desks, and a briefing layer.
- Repeated sections change grid rhythm and hierarchy instead of falling back to uniform cards.

## Interaction system

- Sticky navigation, full-screen mobile menu, working editorial anchors, article routes, work filters, FAQ accordions, and form feedback.
- GSAP enhancements use short directional reveals and media movement on capable desktop devices.
- Mobile parallax is disabled and `prefers-reduced-motion` is respected.
