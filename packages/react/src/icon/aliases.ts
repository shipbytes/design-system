/**
 * Heroicons v1 → v2 names.
 *
 * The dashboard this system came from was built against v1 and picked up v2
 * icons later, so it runs a mix — and v2 renamed 33 of the names in use. A
 * renamed name resolves to no component at all and renders nothing, silently.
 *
 * Kept in lock-step with `config/blade-ui.php`'s `icon_aliases`, which is the
 * Blade side of the same map. `icons/icons.json` carries the full mapping.
 *
 * This is a migration aid, not an API. New code writes v2 names.
 */
export const iconAliases: Record<string, string> = {
  x: 'x-mark',
  search: 'magnifying-glass',
  mail: 'envelope',
  cog: 'cog-6-tooth',
  menu: 'bars-3',
  refresh: 'arrow-path',
  logout: 'arrow-right-on-rectangle',
  'external-link': 'arrow-top-right-on-square',
  'dots-vertical': 'ellipsis-vertical',
  'location-marker': 'map-pin',
  photograph: 'photo',
  'eye-off': 'eye-slash',
  duplicate: 'square-2-stack',
  'pencil-alt': 'pencil-square',
  exclamation: 'exclamation-triangle',
  'view-grid': 'squares-2x2',
  template: 'rectangle-group',
  collection: 'rectangle-stack',
  'badge-check': 'check-badge',
  filter: 'funnel',
  upload: 'arrow-up-tray',
  support: 'lifebuoy',
  globe: 'globe-americas',
  chat: 'chat-bubble-oval-left',
  reply: 'arrow-uturn-left',
  'document-download': 'document-arrow-down',
  'color-swatch': 'swatch',
  'office-building': 'building-office',
  'switch-horizontal': 'arrows-right-left',
  adjustments: 'adjustments-horizontal',
}
