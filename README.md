Common Code for the CUBE Tools

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require cubetools/cube-common-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new CubeTools\CubeCommonBundle\CubeToolsCubeCommonBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Import the routes
-------------------------

To give access to routing information, import routing into `app/config/routing.yml`:
```yaml
# app/config/routing.yml

# ...

_cube_common:
    resource: "@CubeToolsCubeCommonBundle/Resources/config/routing/all.yml"
```

Step 4 (optional): Set different user class
-------------------------------------------

When FosUserBundle is enabled, the user class is set to the one configured for
this bundle.
To use a different user class (for the cube_common.user_settings service), set
your User class in `app/config/config.yml`.
```yaml
# app/config/config.yml
doctrine:
    # ...
    orm:
        # ...
        resolve_target_entities:
            Symfony\Component\Security\Core\User\UserInterface: YourBundle\Entity\YourUser
```

Use the bundle
--------------

Do some index page [filtering](./src/Resources/doc/Filter.rst),
hide table columns dynamically with [ColumnSelector](./src/Resources/doc/ColumnSelector.rst),
or use another functionality (see in [src](./src)).
