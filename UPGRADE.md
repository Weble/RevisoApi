# Upgrades

We'll try to document any major breaking changes here and how to upgrade them. 

## V1 to V2

This is a major release, and a major upgrade as well. Main changes are

- No more Guzzle dependency. You need to have an http client installed in your project, and the library will autodiscover it. More on how in the [README](README.md).
- New namespace. Everything starting with `Webleit\RevisoApi` is now just `Weble\RevisoApi`
- PHP 8.0+ only
- New constructor of the main library, check the [README](README.md) for details.
- `delete()` now is `void`, and throws an exception when delete fails.
- Model data now returns an instance of `Illuminate\Support\Collection`
- `Weble\RevisoApi\Collection` now extends `Illuminate\Support\Collection` instead of wrapping it. Therefore `getData` is not needed anymore.
- `Models` and `Collection` now requires the `Client` as a parameter
