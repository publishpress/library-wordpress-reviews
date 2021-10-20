# PublishPress Five Stars Review Library
Library for displaying a banner asking for a 5-star review.

## Installation

We recommend using composer for adding this library as requirement:

```shell
$ composer require publishpress/publishpress-five-stars-review
```

## How to use it

If your plugin do not load composer's autoloader yet, you need to add the following code:

```php
<?php

require_once 'vendor/autoload.php';
```

The library should be initialized in the method of your plugin that load the main WordPress hooks.
You can add it to the main class fo the plugin. When instantiating it you have to pass 2 params: the plugin slug (the same one used in the URL of the WordPress repository) and the plugin's name.

Pro plugins doesn't require this library, if they use they embed the free plugin. If you instantiate this library on both free and pro plugins, users will probably see duplicated banners.

```php
<?php

use PublishPress\FiveStarsReview\ReviewsController;

class MyPlugin
{
    /**
    * @var  ReviewsController
    */
    private $reviewController;
    
    public function __construct()
    {
        $this->reviewController = new ReviewsController('your-plugin-slug', 'Your Plugin Name');
    }
    
    public function init()
    {
        // .......
        $this->reviewController->init();
    }
    
    // .......
}
```