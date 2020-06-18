# wp-sanitization-demo

WordPress offers many built-in sanitization, validation, and escaping functions. However, it's not always clear which one is most suitable for a given purpose, and often requires trial-and-error to identify usable candidates.

This very simple plugin adds a form that takes a user-provided string and runs it through most of the available functions. Then it shows the results of each, making it obvious which ones merit further consideration.

Use the `[wp-sanitization-demo]` shortcode to add the form to a page.

Please note that this plugin is intended for developers, and hasn't been thoroughly tested for security issues. ;)