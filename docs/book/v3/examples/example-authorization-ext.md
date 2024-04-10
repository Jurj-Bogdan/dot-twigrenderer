# Using the authorization extension

Dot-twigrenderer extends Twig with a function that uses functionality from [dotkernel/dot-authorization](https://github.com/dotkernel/dot-authorization) to check if a logged user is authorized to access a particular resource.

```php
public function isGranted(string $permission = ''): bool;
```

The function returns a boolean value of true if the logged user has access to the requested permission.

## Example usage

Expanding on the example from the authentication extension:

```twig
{% if hasIdentity() %}

    Welcome, {% getIdentity().username %}!
    
    {% if isGranted({{ role }}) %}
        {# your code #}
    {% endif %}

{% endif %}
```
