---
name: ZORZEL
description: Papel y Tinta — a subscription library console with the warmth of an open book and the clarity of ink on paper.
colors:
  # The seven swatches pinned to the brief.
  verde-tinta: "#173F35"
  marfil-papel: "#F4EFE5"
  terracota: "#B85C3E"
  laton: "#C29A52"
  nogal: "#3D2923"
  verde-carbon: "#24312C"
  gris-calido: "#746F65"
  # Readable inks derived from the swatches for small type on warm paper.
  terracota-ink: "#A34E33"
  laton-ink: "#967539"
  gris-calido-ink: "#6B665C"
  success-ink: "#24573F"
  warning-ink: "#6B521F"
  danger-ink: "#8A3625"
  info-ink: "#2E5560"
  # Paper stacked on paper; raised surfaces are lighter than the canvas.
  sidebar-paper: "#EDE5D6"
  navbar-paper: "#F0EADD"
  card-paper: "#FFFCF6"
  surface-paper: "#FAF5EA"
  input-paper: "#FFFDF8"
  text-muted: "#8C8578"
  border-paper: "#DCCFB9"
  divider-paper: "#E8DFCE"
  input-border: "#D3C4AC"
  # Accents, states and legacy utility mappings.
  verde-tinta-hover: "#215344"
  laton-light: "#D2AC66"
  verde-deep: "#24573F"
  terracota-deep: "#8A3625"
  red-wash: "#F6E4DE"
  red-soft: "#EBC9BD"
  red-mid: "#D69A86"
  red-light: "#C57459"
  red-dark: "#4A1F17"
  zinc-deep: "#5A554B"
  zinc-shadow: "#2B3A32"
  accent-soft: "#E3E9E2"
  pure-white: "#FFFFFF"
  # State washes and lines, kept as tokens instead of inline colour.
  wash-green: "rgb(23 63 53 / 0.08)"
  wash-green-strong: "rgb(23 63 53 / 0.07)"
  wash-green-soft: "rgb(23 63 53 / 0.06)"
  brass-line: "rgb(194 154 82 / 0.6)"
  brass-line-soft: "rgb(150 117 57 / 0.4)"
  brass-line-mid: "rgb(150 117 57 / 0.5)"
  brass-focus: "rgb(194 154 82 / 0.3)"
  success-line: "rgb(46 107 79 / 0.45)"
  success-line-soft: "rgb(46 107 79 / 0.42)"
  success-wash: "rgb(46 107 79 / 0.1)"
  warning-line: "rgb(130 99 46 / 0.45)"
  warning-wash: "rgb(194 154 82 / 0.18)"
  danger-line: "rgb(156 63 42 / 0.4)"
  danger-wash: "rgb(184 92 62 / 0.12)"
  danger-wash-banner: "rgb(184 92 62 / 0.11)"
  danger-line-banner: "rgb(184 92 62 / 0.45)"
  info-line: "rgb(62 107 115 / 0.4)"
  info-wash: "rgb(62 107 115 / 0.1)"
  dialog-scrim: "rgb(36 49 44 / 0.52)"
  shadow-color: "rgb(61 41 35 / 0.28)"
  shadow-dialog-color: "rgb(61 41 35 / 0.4)"
  spine-shade: "rgb(61 41 35 / 0.14)"
  wash-walnut: "rgb(61 41 35 / 0.05)"
typography:
  display:
    fontFamily: "Cormorant Garamond, Georgia, serif"
    fontSize: "clamp(2.75rem, 5.2vw, 4rem)"
    fontWeight: 600
    lineHeight: 1
    letterSpacing: "-0.03em"
  headline:
    fontFamily: "Cormorant Garamond, Georgia, serif"
    fontSize: "clamp(1.85rem, 2.4vw, 2.5rem)"
    fontWeight: 600
    lineHeight: 1.05
    letterSpacing: "-0.02em"
  title:
    fontFamily: "Cormorant Garamond, Georgia, serif"
    fontSize: "1.25rem"
    fontWeight: 600
    lineHeight: 1.2
  body:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.72rem"
    fontWeight: 700
    lineHeight: 1.4
    letterSpacing: "0.14em"
  micro:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.76rem"
    fontWeight: 600
    lineHeight: 1.4
  compact:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.78rem"
    fontWeight: 600
    lineHeight: 1.4
  meta:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.8rem"
    fontWeight: 600
    lineHeight: 1.5
  button:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.82rem"
    fontWeight: 600
    lineHeight: 1.2
  body-lg:
    fontFamily: "Manrope, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.7
  subhead:
    fontFamily: "Cormorant Garamond, Georgia, serif"
    fontSize: "1.5rem"
    fontWeight: 600
    lineHeight: 1.1
  auth-display:
    fontFamily: "Cormorant Garamond, Georgia, serif"
    fontSize: "2rem"
    fontWeight: 600
    lineHeight: 1.1
  stat:
    fontFamily: "Cormorant Garamond, Georgia, serif"
    fontSize: "2.5rem"
    fontWeight: 600
    lineHeight: 1
rounded:
  hairline: "1px"
  spine: "3px"
  chart: "4px"
  sm: "6px"
  pagination: "7px"
  md: "8px"
  control: "9px"
  lg: "12px"
  panel: "14px"
  pill: "999px"
spacing:
  xs: "0.35rem"
  sm: "0.6rem"
  md: "1rem"
  lg: "1.5rem"
  xl: "2rem"
  page: "2.5rem"
components:
  button-primary:
    backgroundColor: "{colors.verde-tinta}"
    textColor: "{colors.pure-white}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0.55rem 0.9rem"
  button-primary-hover:
    backgroundColor: "{colors.verde-tinta-hover}"
    textColor: "{colors.pure-white}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0.55rem 0.9rem"
  button-secondary:
    backgroundColor: "{colors.surface-paper}"
    textColor: "{colors.verde-carbon}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0.55rem 0.9rem"
  button-accent:
    backgroundColor: "{colors.laton}"
    textColor: "{colors.nogal}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0.55rem 0.9rem"
  button-danger:
    backgroundColor: "{colors.terracota-deep}"
    textColor: "{colors.pure-white}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0.55rem 0.9rem"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.gris-calido-ink}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0.55rem 0.9rem"
  field:
    backgroundColor: "{colors.input-paper}"
    textColor: "{colors.verde-carbon}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0.6rem 0.75rem"
  card:
    backgroundColor: "{colors.card-paper}"
    textColor: "{colors.verde-carbon}"
    rounded: "{rounded.lg}"
    padding: "1.25rem"
  badge:
    backgroundColor: "{colors.surface-paper}"
    textColor: "{colors.gris-calido-ink}"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: "0.2rem 0.6rem"
  nav-link:
    backgroundColor: "transparent"
    textColor: "{colors.gris-calido-ink}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0.6rem 0.7rem"
  nav-link-active:
    backgroundColor: "{colors.verde-tinta}"
    textColor: "{colors.pure-white}"
    typography: "{typography.body}"
    rounded: "{rounded.md}"
    padding: "0.6rem 0.7rem"
---

# Design System: ZORZEL

<!-- impeccable:design-map -->
Name: ZORZEL
Colors: primary: verde-tinta #173F35; canvas: marfil-papel #F4EFE5; accent: terracota #B85C3E; tertiary: laton #C29A52; text: verde-carbon #24312C; secondary: nogal #3D2923; muted: gris-calido #746F65 (+44 more in the sidecar)
Fonts: Cormorant Garamond (display, headline, title, subhead, auth-display, stat); Manrope (body, label, micro, compact, meta, button, body-lg)
Spacing: xs 0.35rem, sm 0.6rem, md 1rem, lg 1.5rem, xl 2rem, page 2.5rem
Radius: hairline 1px, spine 3px, chart 4px, sm 6px, pagination 7px, md 8px, control 9px, lg 12px, panel 14px, pill 999px
North Star: "The Open Book on the Reading Desk"
Rules:
- The Ink Anchor Rule. Verde tinta owns headers, the primary action and highlighted backgrounds; nothing else spends it.
- The Terracotta Signal Rule. Terracota marks active links and calls to attention. It never becomes a large field.
- The Brass Detail Rule. Latón stays in icons, decorative details and highlighted elements, never in body copy.
- The Two-Voice Rule. Cormorant Garamond speaks only in headings and large numerals; Manrope speaks everywhere else.
- The 11px Floor Rule. Functional text never drops below 11px, including captions, chart labels and table headers.
- The Paper Stack Rule. Depth comes from paper layered on paper; raised surfaces are lighter than the canvas and carry one soft walnut shadow.
Elevation:
- Ambient panel: 0 18px 40px -24px rgb(61 41 35 / 0.28)
- Dialog: 0 24px 60px -24px rgb(61 41 35 / 0.4)
Don't:
- Don't reintroduce the retired dark walnut/burgundy theme or add a theme switcher; the light paper theme is the only theme.
- Don't use gradients, textures, decorative gold or zero-blur block shadows.
- Don't shrink labels, chart captions or table headers below the 11px floor.
- Don't rely on colour alone for status; pair it with a written state label.
- Don't set small text in raw latón or terracota on paper; use the darker derived inks.
Do:
- Do keep every surface in the marfil-papel family and let cards sit one step lighter than the canvas.
- Do reserve verde tinta for headers, buttons and highlighted backgrounds.
- Do use terracota for active links and callouts, and latón for icons and decorative detail.
- Do use Lucide-style drawn icons at one consistent stroke weight.
- Do keep functional text at 11px or above and placeholders at gris cálido.
- Do keep empty states typographic inside tables: no nested card and no dashed well.
- Do map inherited green/red utility classes onto the paper-daylight tints so stock auth and settings screens stay in world.
- Do use the ambient panel shadow for raised containers only.
Full: .impeccable/design-full.md (read a section when you need its detail)
Lookup: `& 'C:\Users\RYZEN 3\.config\opencode\skills\impeccable\scripts\impeccable.cmd' design-lookup <term>` prints one token, component or section, with its file.

## Overview

**Creative North Star: "The Open Book on the Reading Desk"**

ZORZEL now reads like a book laid open under a desk lamp rather than a darkened reading room. Marfil papel (#F4EFE5) is the general ground: warm, matte and faintly fibrous in feel without any texture being drawn. Verde carbón (#24312C) carries the main text, gris cálido (#746F65) the descriptions, and nogal (#3D2923) the secondary structures. The result is calmer and more legible in daylight, which is when library staff actually work.

Colour is rationed by role. Verde tinta (#173F35) anchors headers, buttons and highlighted backgrounds. Terracota (#B85C3E) is the signal for active links and calls to attention. Latón (#C29A52) is the craft layer: icons, decorative lines and highlighted elements. Small type never uses raw latón or terracota on paper; the derived inks keep every label above 4.5:1.

Cormorant Garamond supplies editorial headings and large numerals, Manrope keeps controls, labels and tabular text quiet and legible. Icons are drawn with a single Lucide-like stroke so the whole product reads as one authored system rather than a starter kit.

**Key Characteristics:**
- Paper-on-paper layering with a single verde-tinta anchor, terracota signals and restrained latón detail.
- Editorial display type over a neutral, highly legible UI face.
- Dense tables and compact stats with generous section separation.
- One icon language, one soft shadow vocabulary, one light theme.

## Colors

The palette is warm paper and saturated ink. Each swatch owns a job; none is decoration without a relationship to hierarchy or state.

### Primary
- **Verde Tinta** (#173F35): Page headers, primary buttons, active navigation fill and highlighted backgrounds. 11.67:1 against white, so it is the safe home for the strongest action.
- **Verde Tinta Hover** (#215344): The single step lighter on primary-button hover.

### Secondary
- **Terracota** (#B85C3E): Accents, active links and callouts. Use the darker **Terracota Ink** (#A34E33) when the signal is small text on paper.
- **Nogal** (#3D2923): Dark secondary elements, strong borders and the label on a latón button.

### Tertiary
- **Latón** (#C29A52): Decorative details, icons and highlighted elements. Use **Latón Ink** (#967539) for icons that must meet 3:1 on paper.

### Neutral
- **Marfil Papel** (#F4EFE5): The general background, warm like a book page.
- **Verde Carbón** (#24312C): Main text, 11.82:1 on the canvas.
- **Gris Cálido** (#746F65): Secondary text and descriptions, with the one-step darker **Gris Cálido Ink** (#6B665C) for small type on any surface.
- **Sidebar Paper** (#EDE5D6) / **Navbar Paper** (#F0EADD): Recessed chrome, still inside the paper family.
- **Card Paper** (#FFFCF6) / **Surface Paper** (#FAF5EA) / **Input Paper** (#FFFDF8): Raised surfaces, one to three steps lighter than the canvas.
- **Border Paper** (#DCCFB9) / **Divider Paper** (#E8DFCE): Hairlines and row separators.

**The Ink Anchor Rule.** Verde tinta owns headers, the primary action and highlighted backgrounds. When everything is green, nothing is the action.

**The Terracotta Signal Rule.** Terracota marks active links and calls to attention. It never becomes a large field and never carries small body copy without its darker ink.

**The Brass Detail Rule.** Latón stays in icons, decorative detail and highlighted elements. It earns its force by being rare.

**The Derived-Ink Rule.** The seven brief swatches are canonical; the derived inks exist so the same hue stays legible at 11px. Never substitute a generic gray for them.

## Typography

**Display Font:** Cormorant Garamond (with Georgia, serif)
**Body Font:** Manrope (with ui-sans-serif, system-ui, sans-serif)

**Character:** Cormorant is editorial and unhurried; Manrope is compact and workmanlike. Their contrast is the whole voice — the page is read in two registers, never a blended one.

### Hierarchy
- **Display** (600, clamp(2.75rem, 5.2vw, 4rem), line-height 1): Public hero headline only.
- **Headline** (600, clamp(1.85rem, 2.4vw, 2.5rem), line-height 1.05): Page titles.
- **Title** (600, 1.25rem, line-height 1.2): Card and panel titles.
- **Body** (400, 0.875rem, line-height 1.5): Controls, tables and supporting copy; 65–75ch measure.
- **Label** (700, 0.72rem, 0.14em, uppercase): Navigation groups, stat labels and table headers.
- **Button** (600, 0.82rem): All button labels.

**The Two-Voice Rule.** Cormorant Garamond speaks only in headings and large numerals; Manrope speaks everywhere else.

**The 11px Floor Rule.** Functional text never drops below 11px, including captions, chart labels and table headers.

## Layout

Desktop-first console. A fixed 264px sidebar holds the ZORZEL wordmark, grouped navigation and the active verde-tinta item with a latón marker. A 4rem sticky top bar carries breadcrumb, search and profile. Content is capped at 1500px, with an inner page column at 1240px and 2rem of top padding.

Cards use a four-column stat grid that collapses to two columns below 1100px and one below 640px. The collection-health section is a 1.15fr / 0.85fr two-panel grid that stacks at the same breakpoint. Tables stay dense with 0.75–0.8rem cell padding and a hairline divider language.

## Elevation & Depth

Depth is paper stacked on paper: raised surfaces are lighter than the canvas and carry one soft, offset walnut shadow. Nothing floats on a zero-blur halo, and nothing is textured or gridded.

### Shadow Vocabulary
- **Ambient panel** (`box-shadow: 0 18px 40px -24px rgb(61 41 35 / 0.28)`): Cards, table wrappers and auth panels.
- **Dialog** (`box-shadow: 0 24px 60px -24px rgb(61 41 35 / 0.4)`): Modal surfaces only.

**The Paper Stack Rule.** Surfaces are flat at rest; depth is a material property of raised containers, not a response to interaction.

## Shapes

Moderate, bookish radii: 3px on book spines, 6px on small marks, 8px on controls and buttons, 12px on panels and cards, 14px on dialogs, 999px on pills. Hairlines are 1px in border paper. Corners are never fully square except the printed spine.

## Components

### Buttons
- **Shape:** Gently curved (8px radius), 0.55rem 0.9rem padding, Manrope 600 at 0.82rem.
- **Primary:** Verde tinta fill, white label; hover steps to verde tinta hover.
- **Secondary:** Surface paper fill, border paper stroke, verde carbón label.
- **Accent:** Latón fill with nogal label; hover steps to latón light.
- **Danger:** Terracota-deep fill with white label; hover darkens to terracota ink.

### Chips
- **Style:** Pill (999px), surface paper fill, hairline border, 0.72rem uppercase label.
- **State:** Success, warning, danger, info and accent variants use tinted washes with derived dark inks so the written state stays readable without relying on hue alone.

### Cards / Containers
- **Corner Style:** 12px.
- **Background:** Card paper (#FFFCF6), one step lighter than the canvas.
- **Shadow Strategy:** Ambient panel.
- **Border:** 1px border paper.
- **Internal Padding:** 1.25rem.

### Inputs / Fields
- **Style:** Input paper fill, input-border stroke, 8px radius, verde carbón text.
- **Focus:** Latón ink border with a soft latón focus ring; no outline jump.
- **Error / Disabled:** Terracota-tinted banner with a written error; disabled fields drop to 55% opacity.

### Navigation
- **Style:** Full-width links in the sidebar, 8px radius, gris cálido ink at rest.
- **Active:** Verde tinta fill, white label, latón border and latón dot marker.
- **Hover:** A faint verde-tinta wash; focus uses a latón ring.

### Book Shelf (signature)
- **Style:** A row of printed spines in a recessed surface-paper tray. Spines alternate between card paper, nogal-tinted paper and verde-tinta-tinted paper, each with two latón rules. Hover catches a warm light while the rest recede; reduced motion keeps the spines still.

## Do's and Don'ts

### Do:
- **Do** keep every surface in the marfil-papel family; the only theme is light.
- **Do** reserve verde tinta for headers, buttons and highlighted backgrounds.
- **Do** use terracota for active links and callouts, and latón for icons and decorative detail.
- **Do** use the derived inks (#A34E33, #967539, #6B665C) for small text instead of the raw swatches.
- **Do** use Lucide-style drawn icons at one consistent stroke weight.
- **Do** keep functional text at 11px or above and placeholders at gris cálido.
- **Do** keep empty states typographic inside tables: no nested card and no dashed well.
- **Do** use the ambient panel shadow for raised containers only.

### Don't:
- **Don't** reintroduce the retired dark walnut/burgundy theme or add a theme switcher.
- **Don't** use gradients, textures, decorative gold or zero-blur block shadows.
- **Don't** shrink labels, chart captions or table headers below the 11px floor.
- **Don't** rely on colour alone for status; pair it with a written state label.
- **Don't** set small text in raw latón or terracota on paper.
