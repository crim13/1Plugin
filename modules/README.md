# 1Plugin Divi 5 Modules

This directory is the module library workspace for native Divi 5 modules.

Each module should keep the same high-level shape:

- `server/index.php` registers the module and renders frontend output.
- `module.json` defines Divi 5 metadata and settings used by both server render and Visual Builder.
- `visual-builder/src/index.jsx` registers the Visual Builder preview.
- `visual-builder/build/` stores compiled Visual Builder assets.

The active module library includes Menu and FAQ. Placeholder modules are intentionally excluded from release builds until they have a complete server renderer and Visual Builder implementation.
