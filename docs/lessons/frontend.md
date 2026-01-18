---
tags: [vue, inertia, chartjs, ui-components, design-system, dark-mode, css, toasts, forms]
updated: 2026-01-18
---

# Frontend Lessons

> **Quick summary for agents**: Use `useChartColors` composable for Chart.js theming with MutationObserver for dark mode detection. Always use semantic CSS variables (`text-muted-foreground`) instead of hardcoded colors (`text-neutral-600`). Use `vue-sonner` for toasts with group selectors for styling. Inertia v2 deferred props need skeleton loading states. Use regular `<a>` tags for OAuth flows, not Inertia `<Link>`.

---

## Sprint 5: Chart.js Dark Mode Fix

### What went wrong?
- Chart.js renders to canvas and evaluates CSS custom properties like `hsl(var(--primary))` once at render time
- When theme switches to dark mode, charts remained with light mode colors (black on black text)
- CSS variables are strings passed to the canvas context - they don't dynamically update when the theme changes
- Tooltips, grid lines, and axis labels were all invisible in dark mode

### What went well?
- Created `useChartColors` composable that reads computed CSS values and reacts to theme changes
- MutationObserver pattern cleanly detects when `.dark` class is added/removed from document root
- Using Vue's `:key` prop with a `themeKey` counter forces clean chart re-render on theme change
- Solution is reusable for any future Chart.js components

### Why we chose this direction
- **Composable over inline fixes**: Centralized color logic in `useChartColors.ts` means all charts get the same treatment. Adding dark mode to new charts is one import.
- **MutationObserver over watch**: Theme changes via `.dark` class on `<html>`. MutationObserver is the browser-native way to detect attribute changes on elements outside Vue's reactivity.
- **Key-based re-render over chart.update()**: Chart.js `update()` method doesn't reliably refresh all visual properties. Using Vue's key mechanism guarantees a clean slate.
- **getComputedStyle over parsing CSS**: Reading computed values ensures we get the actual resolved color, accounting for any CSS specificity or overrides.
- **requestAnimationFrame delay**: CSS variables need a tick to update after class change. RAF ensures we read the new values, not stale ones.

### Code Pattern
```typescript
// composables/useChartColors.ts
const { colors, themeKey } = useChartColors();

// In template - key forces re-render when theme changes
<Line :key="themeKey" :data="chartData" :options="chartOptions" />

// Colors are reactive - use in computed chart options
borderColor: colors.value.primary
```

---

## Sprint 5: Analytics Dashboard UI

### What went wrong?
- Chart.js tooltip callback types expect `unknown` for `raw` property, not `number` - TypeScript complained until we added proper type assertions
- Vite build warning about 500KB+ chunks - Chart.js and lucide-vue-next are large libraries

### What went well?
- Inertia v2 deferred props work beautifully - dashboard loads instantly, data fills in asynchronously with skeleton states
- Chart.js + vue-chartjs integration was straightforward - computed properties for reactive chart data
- Code splitting reduced Dashboard chunk from 205KB to 30KB - Chart.js now loads separately
- StatCard component with loading prop creates reusable skeleton pattern

### Why we chose this direction
- **Deferred props over eager loading**: Dashboard could fetch all data upfront, but deferred props give instant page load with progressive enhancement. Users see the shell immediately.
- **Manual chunks in Vite**: Default bundling put everything in one chunk. Splitting chart/ui/icons/vendor improves caching - users don't re-download Chart.js when app code changes.
- **chunkSizeWarningLimit: 550**: lucide-vue-next is 519KB but gzips to 130KB. Acceptable tradeoff for comprehensive icon library. Warning suppression is explicit.
- **StatCard component with loading prop**: Reusable pattern - same component renders skeleton or value based on loading state. Reduces template duplication.

---

## UI Enhancement: Vue-Sonner Toasts & FormSaveWidget

### What went wrong?
- Initially installed `sonner` (React version) instead of `vue-sonner` (Vue port) - had to uninstall and reinstall the correct package
- Found multiple places using hardcoded neutral colors (`text-neutral-600`, `decoration-neutral-300`) instead of semantic CSS variables - these don't match the warm color palette in light mode
- Some components had redundant `dark:` variants when the CSS variables already handle light/dark differences
- Vue-sonner's `ToastOptions` type uses `classes` not `classNames` - TypeScript caught this mismatch in CI even though it worked at runtime

### What went well?
- Vue-sonner integrates cleanly with Vue 3 - just wrap the Toaster in app.ts with `h()` function alongside the main App component
- Toaster styling matches design system by using group selectors (`group-[.toaster]:bg-card`) to target toast classes
- FormSaveWidget composable with `useFormSaveWidget` provides clean separation between dirty state tracking and UI rendering
- Teleport to body ensures the save widget renders above all content regardless of z-index stacking
- Audit found and fixed 7 files with hardcoded neutral colors that clashed with warm color palette

### Why we chose this direction
- **Vue-sonner over other toast libraries**: Vue-sonner is the official Vue port of Sonner, matching patterns from shadcn-vue. Other libraries like vue-toastification have different APIs and styling approaches.
- **Composable + Component pattern for FormSaveWidget**: Separating the state logic (`useFormSaveWidget`) from the UI (`FormSaveWidget`) allows the widget to work with any form using Inertia's useForm. The composable handles isDirty tracking, save/discard actions, and toast notifications.
- **Teleport over fixed positioning within layout**: Fixed position elements inside scrollable containers can have z-index issues. Teleporting to body guarantees visibility.
- **Semantic CSS variables over hardcoded colors**: Using `text-muted-foreground` instead of `text-neutral-600` ensures colors adapt to both light and dark themes while respecting the warm color palette (`hsl(20 8% 46%)` in light, `hsl(30 10% 60%)` in dark).
- **Decoration-border over decoration-neutral**: Link underline decorations should use the border color variable which matches the warm palette (`hsl(30 15% 90%)` in light mode) rather than neutral grays that feel cold against warm backgrounds.

### Code Pattern
```typescript
// app.ts - Adding Toaster at root level
createApp({
    render: () => h('div', [h(App, props), h(Toaster)]),
})

// useFormSaveWidget composable
const { isDirty, processing, recentlySuccessful, save, discard } =
    useFormSaveWidget({
        form,
        route: updateProfile,
        successMessage: 'Profile updated successfully',
    });

// FormSaveWidget with Teleport
<Teleport to="body">
    <div :data-visible="isVisible" class="fixed bottom-6 ...">
        <!-- Widget content -->
    </div>
</Teleport>
```

### Design System Guidelines Established
1. **Always use semantic CSS variables**: `text-foreground`, `text-muted-foreground`, `bg-card`, `border-border`, etc.
2. **Avoid hardcoded neutral/gray colors**: They clash with the warm color palette
3. **Use `dark:` variants only when necessary**: CSS variables already handle theme switching
4. **Toast styling via classes prop**: Use group selectors to style toasts consistently with the design system
5. **Light mode accent**: The amber accent `hsl(32 95% 55%)` should be visible but not overwhelming in light mode - use with reduced opacity

---

## Settings Page: Sticky Sidebar & Custom Scrollbar

### What went wrong?
- Nothing significant - straightforward CSS enhancement

### What went well?
- Sticky sidebar keeps navigation visible when scrolling long settings content
- Custom scrollbar with gradient thumb matches brand orange color (`hsl(32 95% 55%)`)
- Dark mode scrollbar colors properly use CSS class targeting (`.dark .settings-sidebar-scroll`)
- Removing `min-h-full` constraint fixed unnecessary layout forcing

### Why we chose this direction
- **Sticky over fixed**: `position: sticky` respects document flow and works within flex containers. Fixed positioning would require manual offset calculations and could overlap content.
- **max-height with calc()**: `lg:max-h-[calc(100vh-6rem)]` ensures sidebar never extends beyond viewport while leaving room for header/padding. Enables scroll when many nav items exist.
- **Custom scrollbar in utilities layer**: Placed in `@layer utilities` so Tailwind's `scrollbar-thin` and other scrollbar utilities can coexist. Named class `settings-sidebar-scroll` is specific enough to avoid conflicts.
- **Gradient scrollbar thumb**: Subtle gradient adds depth without being distracting. Matches the accent color used throughout the app for interactive elements.

---

## Enhanced User Settings Hub

### What went wrong?
- ESLint flagged computed functions without default returns in switch statements - TypeScript was happy but ESLint's `vue/return-in-computed-property` rule requires explicit defaults
- Unused `router` import in Data.vue was caught by ESLint - leftover from initial implementation approach

### What went well?
- SettingCard component with status indicators provides visual feedback at a glance - users can see GitHub connection status, 2FA state, etc. without navigating
- Switch component using reka-ui integrates cleanly with Vue reactivity - `@update:checked` emits work seamlessly with `router.patch()`
- SettingSection/SettingRow pattern creates consistent visual hierarchy - new settings pages can follow the established pattern
- ExportUserDataAction handles both JSON and CSV formats cleanly - match expression keeps the logic readable

### Why we chose this direction
- **Settings hub over direct navigation**: Central hub gives overview of account state. Users see at a glance what needs attention (unverified email, 2FA disabled).
- **Status indicators with semantic colors**: Emerald for enabled/good, amber for warning, sky for info - follows established UX patterns. Monochromatic palette with accent colors.
- **Form-based file download over fetch**: Using hidden form submission triggers browser's native download handling. Avoids blob handling and memory issues for large exports.

---

## Entry Template

```markdown
## [Feature Name]

### What went wrong?
- Issue description and root cause

### What went well?
- Success description and contributing factors

### Why we chose this direction
- Decision and reasoning
```
