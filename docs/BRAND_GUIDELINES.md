# GitPulse Brand Guidelines

This document establishes the visual identity and brand standards for GitPulse. Follow these guidelines to maintain consistency across all applications, marketing materials, and communications.

---

## Brand Overview

### Mission Statement
GitPulse transforms Git commit activity into actionable insights, helping developers and teams understand their productivity patterns and celebrate their achievements.

### Brand Personality
- **Insightful** - We illuminate patterns others miss
- **Empowering** - We help developers grow, not judge
- **Clean** - We present complex data simply
- **Trustworthy** - We handle sensitive data with care

### Brand Voice
- Clear and concise
- Data-driven but human
- Encouraging without being patronizing
- Professional yet approachable

---

## Logo

### Primary Logo (Logotype)
The GitPulse logotype combines a pulse/heartbeat icon with the wordmark. Use this as the primary brand identifier.

```
   ╱╲
──╱  ╲──  GitPulse
     ╲╱
```

### Logomark (Icon)
The pulse icon can be used independently as an app icon, favicon, or social media avatar.

### Clear Space
Maintain clear space around the logo equal to the height of the "G" in GitPulse on all sides. Never crowd the logo with other elements.

### Minimum Size
- **Logotype**: Minimum width of 120px (digital) or 1 inch (print)
- **Logomark**: Minimum size of 24px × 24px (digital) or 0.25 inch (print)

### Logo Variations
| Variation | Use Case |
|-----------|----------|
| Full color on light | Primary use on white/light backgrounds |
| Full color on dark | Primary use on dark backgrounds |
| Monochrome (dark) | Single-color applications on light backgrounds |
| Monochrome (light) | Single-color applications on dark backgrounds |

---

## Color Palette

### Primary Colors

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| **Pulse Blue** | `#0066FF` | 0, 102, 255 | Primary brand color, CTAs, links |
| **Pulse Navy** | `#1A1F36` | 26, 31, 54 | Headings, primary text, dark mode backgrounds |

### Secondary Colors

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| **Insight Purple** | `#7C3AED` | 124, 58, 237 | AI features, insights, premium features |
| **Growth Green** | `#10B981` | 16, 185, 129 | Success states, positive metrics, increases |
| **Alert Amber** | `#F59E0B` | 245, 158, 11 | Warnings, attention states |
| **Error Red** | `#EF4444` | 239, 68, 68 | Errors, destructive actions, decreases |

### Neutral Colors

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| **Gray 900** | `#111827` | 17, 24, 39 | Primary text |
| **Gray 700** | `#374151` | 55, 65, 81 | Secondary text |
| **Gray 500** | `#6B7280` | 107, 114, 128 | Muted text, placeholders |
| **Gray 300** | `#D1D5DB` | 209, 213, 219 | Borders, dividers |
| **Gray 100** | `#F3F4F6` | 243, 244, 246 | Backgrounds, cards |
| **White** | `#FFFFFF` | 255, 255, 255 | Page backgrounds |

### Semantic Colors

| Purpose | Light Mode | Dark Mode |
|---------|------------|-----------|
| **Background (default)** | `#FFFFFF` | `#1A1F36` |
| **Background (muted)** | `#F3F4F6` | `#252B43` |
| **Text (primary)** | `#111827` | `#F9FAFB` |
| **Text (secondary)** | `#6B7280` | `#9CA3AF` |
| **Border (default)** | `#D1D5DB` | `#374151` |

### Chart Colors
For data visualizations, use these colors in order:

1. `#0066FF` - Pulse Blue
2. `#7C3AED` - Insight Purple
3. `#10B981` - Growth Green
4. `#F59E0B` - Alert Amber
5. `#EC4899` - Pink
6. `#06B6D4` - Cyan

---

## Typography

### Font Families

| Purpose | Font | Fallback Stack |
|---------|------|----------------|
| **Headings** | Inter | system-ui, -apple-system, sans-serif |
| **Body** | Inter | system-ui, -apple-system, sans-serif |
| **Code** | JetBrains Mono | ui-monospace, monospace |

### Type Scale

| Style | Size | Weight | Line Height | Usage |
|-------|------|--------|-------------|-------|
| **Display** | 48px / 3rem | 700 | 1.1 | Hero sections, landing pages |
| **H1** | 36px / 2.25rem | 700 | 1.2 | Page titles |
| **H2** | 30px / 1.875rem | 600 | 1.25 | Section headings |
| **H3** | 24px / 1.5rem | 600 | 1.3 | Subsection headings |
| **H4** | 20px / 1.25rem | 600 | 1.4 | Card titles |
| **Body Large** | 18px / 1.125rem | 400 | 1.6 | Lead paragraphs |
| **Body** | 16px / 1rem | 400 | 1.5 | Default body text |
| **Body Small** | 14px / 0.875rem | 400 | 1.5 | Secondary text, captions |
| **Caption** | 12px / 0.75rem | 500 | 1.4 | Labels, metadata |

### Font Weights

| Name | Value | Usage |
|------|-------|-------|
| Regular | 400 | Body text |
| Medium | 500 | Emphasis, labels |
| Semibold | 600 | Subheadings, buttons |
| Bold | 700 | Headings, strong emphasis |

### Best Practices
- Maintain a maximum line length of 75-80 characters for readability
- Use left-aligned text (avoid justified text)
- Use semantic heading hierarchy (don't skip levels)
- Avoid using color alone for emphasis

---

## Spacing

### Base Unit
GitPulse uses a **4px base unit** for all spacing. All spacing values should be multiples of 4px.

### Spacing Scale

| Token | Value | Usage |
|-------|-------|-------|
| `--space-0` | 0px | No spacing |
| `--space-1` | 4px | Tight spacing, icon gaps |
| `--space-2` | 8px | Compact elements, inline spacing |
| `--space-3` | 12px | List items, small gaps |
| `--space-4` | 16px | Default padding, card content |
| `--space-5` | 20px | Medium spacing |
| `--space-6` | 24px | Section padding |
| `--space-8` | 32px | Large section gaps |
| `--space-10` | 40px | Page sections |
| `--space-12` | 48px | Major sections |
| `--space-16` | 64px | Hero sections |

### Component Spacing

| Component | Padding | Gap |
|-----------|---------|-----|
| Button (sm) | 8px 12px | - |
| Button (md) | 10px 16px | - |
| Button (lg) | 12px 24px | - |
| Card | 16px | - |
| Card (lg) | 24px | - |
| Input | 10px 12px | - |
| Form fields | - | 16px |
| Metric cards | - | 16px |

---

## Iconography

### Style Guidelines
- Use outlined icons (2px stroke) for UI elements
- Use filled icons for active/selected states
- Maintain 24px × 24px default size
- Use current color for flexibility

### Recommended Icon Libraries
- **Heroicons** (primary) - Matches our minimal aesthetic
- **Lucide** (alternative) - Good for specialized icons

---

## Components

### Buttons

| Type | Background | Text | Border | Usage |
|------|------------|------|--------|-------|
| Primary | `#0066FF` | `#FFFFFF` | none | Main CTAs |
| Secondary | `#F3F4F6` | `#374151` | none | Secondary actions |
| Outline | transparent | `#0066FF` | `#0066FF` | Tertiary actions |
| Ghost | transparent | `#6B7280` | none | Subtle actions |
| Danger | `#EF4444` | `#FFFFFF` | none | Destructive actions |

### Cards
- Default border radius: 8px
- Default shadow: `0 1px 3px rgba(0,0,0,0.1)`
- Hover shadow: `0 4px 6px rgba(0,0,0,0.1)`

### Metrics Display
When displaying productivity metrics:
- Use **Growth Green** (`#10B981`) for positive changes
- Use **Error Red** (`#EF4444`) for negative changes
- Include directional arrows (↑ ↓) alongside percentages
- Format numbers with appropriate precision (e.g., 1.2k, 3.5M)

---

## Data Visualization

### Chart Guidelines
- Always include axis labels and legends
- Use color-blind friendly palettes
- Provide data table alternatives for accessibility
- Animate transitions at 200ms ease-out

### Impact Score Visualization
The Impact Score (0-100) should use a gradient scale:
- 0-25: Gray (`#9CA3AF`) - Low impact
- 26-50: Blue (`#0066FF`) - Moderate impact
- 51-75: Purple (`#7C3AED`) - High impact
- 76-100: Green (`#10B981`) - Exceptional impact

---

## Logo Usage: Do's and Don'ts

### Do's
- Use the logo to link to GitPulse
- Maintain proper clear space around the logo
- Use approved color variations only
- Scale proportionally

### Don'ts
- Don't modify the logo colors outside approved variations
- Don't stretch, rotate, or distort the logo
- Don't add effects (shadows, gradients, outlines)
- Don't place the logo on busy backgrounds
- Don't recreate or redraw the logo
- Don't use the logo as part of a sentence

---

## Accessibility

### Color Contrast
- All text must meet WCAG 2.1 AA standards
- Body text: minimum 4.5:1 contrast ratio
- Large text (18px+): minimum 3:1 contrast ratio
- Interactive elements: minimum 3:1 contrast ratio

### Focus States
- Use a visible focus ring (`2px solid #0066FF`)
- Focus ring offset: 2px
- Never remove focus indicators

---

## File Naming Conventions

### Assets
```
gitpulse-logo-{variant}-{size}.{format}
gitpulse-icon-{variant}-{size}.{format}
```

Examples:
- `gitpulse-logo-color-light-1200.png`
- `gitpulse-logo-mono-dark-600.svg`
- `gitpulse-icon-color-256.png`

---

## Contact

For brand-related questions or to request assets, contact the GitPulse team.

---

*Last updated: January 2026*
