## Providing icons

On Drupal 11.1+, Micro Toolbar reads the same `options.icon` structure as core
Navigation. Define a standard core Icon API pack in `MODULE.icons.yml`, then
reference it from `MODULE.links.menu.yml`:

```yaml
example.admin:
  title: Example
  parent: system.admin
  route_name: example.dashboard
  options:
    icon:
      pack_id: example
      icon_id: dashboard
      settings:
        size: 18
```

The pack belongs to the providing module or theme, not Micro Toolbar. Its
libraries bubble through Drupal's render API. Keep icons decorative and
non-interactive; the menu title provides the accessible link name. Icon packs
can use SVG, images, or other renderers supported by core Icon API. Core still
marks Icon API experimental, so check integration when upgrading Drupal.

Missing packs/icons fall back to the built-in icon for known routes, or the grid icon for other links. Full icon
support requires both pack_id and icon_id. Navigation-specific default pack
names and navigation-only alter hooks are not inferred or invoked.
