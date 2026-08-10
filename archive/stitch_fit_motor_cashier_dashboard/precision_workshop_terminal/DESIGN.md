---
name: Precision Workshop Terminal
colors:
  surface: '#fbf8fc'
  surface-dim: '#dbd9dd'
  surface-bright: '#fbf8fc'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f5f3f6'
  surface-container: '#efedf0'
  surface-container-high: '#eae7eb'
  surface-container-highest: '#e4e2e5'
  on-surface: '#1b1b1e'
  on-surface-variant: '#45464e'
  inverse-surface: '#303033'
  inverse-on-surface: '#f2f0f3'
  outline: '#75777f'
  outline-variant: '#c6c6cf'
  surface-tint: '#515d83'
  primary: '#172547'
  on-primary: '#ffffff'
  primary-container: '#2e3b5e'
  on-primary-container: '#99a6cf'
  inverse-primary: '#b8c5f0'
  secondary: '#006c49'
  on-secondary: '#ffffff'
  secondary-container: '#6cf8bb'
  on-secondary-container: '#00714d'
  tertiary: '#705c26'
  on-tertiary: '#ffffff'
  tertiary-container: '#c2a96b'
  on-tertiary-container: '#4f3d09'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2ff'
  primary-fixed-dim: '#b8c5f0'
  on-primary-fixed: '#0b1a3b'
  on-primary-fixed-variant: '#394669'
  secondary-fixed: '#6ffbbe'
  secondary-fixed-dim: '#4edea3'
  on-secondary-fixed: '#002113'
  on-secondary-fixed-variant: '#005236'
  tertiary-fixed: '#fce09d'
  tertiary-fixed-dim: '#dec483'
  on-tertiary-fixed: '#241a00'
  on-tertiary-fixed-variant: '#564510'
  background: '#fbf8fc'
  on-background: '#1b1b1e'
  surface-variant: '#e4e2e5'
  surface-default: '#FFFFFF'
  background-muted: '#F3F4F6'
  member-gold: '#F59E0B'
  status-incoming: '#3B82F6'
  status-alert: '#EF4444'
typography:
  headline-display:
    fontFamily: Hanken Grotesk
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Hanken Grotesk
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Hanken Grotesk
    fontSize: 16px
    fontWeight: '500'
    lineHeight: 24px
  body-sm:
    fontFamily: Hanken Grotesk
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  data-mono:
    fontFamily: JetBrains Mono
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
    letterSpacing: 0.02em
  label-caps:
    fontFamily: Hanken Grotesk
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  container-margin: 1rem
  column-gap: 0.75rem
  stack-compact: 0.5rem
  cell-padding: 0.375rem
---

## Brand & Style

The design system is engineered for the high-velocity environment of a motorcycle workshop. The personality is **utilitarian, authoritative, and dependable**, ensuring that even inexperienced staff can navigate complex transactions without cognitive overload.

The visual style is **Corporate / Modern** with a lean towards **High-Efficiency Minimalism**. It prioritizes information density and data legibility over decorative elements. By utilizing a zero-scroll, single-screen (100vh) architecture, the system provides a comprehensive "command center" feel that minimizes navigation clicks and keeps critical financial and service data visible at all times.

## Colors

The color palette is functionally driven to guide the user's eye toward specific outcomes. 

- **Primary (Dark Slate Blue):** Used for structural elements like headers, navigation, and sidebar containers to provide a stable, professional anchor.
- **Success (Emerald Green):** Reserved exclusively for finality—checkout buttons, grand totals, and "Service Complete" status indicators.
- **Surface & Background:** A clean white surface on a very light gray background creates clear separation between different data modules without needing heavy borders.
- **Named Accents:** Vibrant status colors differentiate between "Gold Member" benefits and critical system alerts (red), ensuring immediate recognition of tiered pricing or urgent mechanical updates.

## Typography

The system uses **Hanken Grotesk** for its sharp, contemporary feel and excellent legibility at small sizes. For data-heavy strings such as Service Numbers, License Plates, and Currency, **JetBrains Mono** is employed to ensure character distinction and vertical alignment in tables.

Hierarchy is established through weight and color contrast rather than excessive size changes to maintain a compact layout. All labels for input fields use a bold, uppercase style to differentiate them clearly from user-entered data.

## Layout & Spacing

This design system utilizes a **Fixed 3-Column Grid** constrained to `100vh` to prevent vertical scrolling. 

- **Left Column (25%):** Persistent Customer & Vehicle metadata.
- **Center Column (50%):** Dynamic Workflow (Tabs/Inputs).
- **Right Column (25%):** Live Financial Totals and Primary Action.

The spacing rhythm is **tight and compact**. A baseline 4px grid is used to ensure elements sit close together, maximizing the information density required for a cashier's "at-a-glance" workflow. Internal scrolling is only permitted within specific data tables (e.g., the manifest of parts) to keep the global UI static.

## Elevation & Depth

To maintain a clean and modern appearance, the system avoids heavy shadows. Instead, it uses **Tonal Layering** and **Low-Contrast Outlines**:

- **Primary Level:** The background is `background-muted` gray.
- **Secondary Level:** Functional cards and input areas are pure white with a subtle 1px border (#E5E7EB).
- **Floating Elements:** Modals and pop-overs use a soft, large-radius ambient shadow to differentiate themselves from the static grid.
- **Depth through Saturation:** High-priority interaction points use solid color fills, while secondary information (read-only data) uses de-saturated text or light-tinted backgrounds.

## Shapes

The shape language is **Soft (0.25rem)**. This slight rounding provides a modern feel without the "playfulness" of more rounded systems, maintaining the professional tone of an industrial service dashboard. 

Special exceptions are made for **Status Badges**, which use a pill-shape (full rounding) to differentiate them from buttons and input fields, and **License Plate displays**, which utilize a custom rectangular frame to mimic physical plates.

## Components

- **Buttons:** 
  - *Primary (Emerald Green):* Reserved for "Print & Complete."
  - *Secondary (Slate Blue):* Used for adding items or navigating tabs.
  - *Ghost:* Used for "Cancel" or "Go Back" to minimize visual noise.
- **Status Badges:** Compact pill-shaped containers with high-contrast text. Color-coded: Blue (In Progress), Green (Done), Yellow (Waiting).
- **Input Fields:** Flat design with a subtle bottom-border focus state. For the cashier dashboard, focus states should be highly visible (using a 2px Primary Blue stroke) to support keyboard-only navigation.
- **Tab System:** Simple underline or block-fill tabs with no height change between states. Transitions should be instantaneous to support high-speed data entry.
- **Data Cards:** Grouped information blocks (e.g., Customer Info) should use a shared header style and light padding to separate data points without wasting vertical space.
- **License Plate Badge:** A specialized component with a black border and monospaced font to represent the vehicle ID uniquely.