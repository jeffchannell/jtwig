# JTwig

Joomla! Twig integration

http://twig.sensiolabs.org/

### Parameters

#### Process Admin

When enabled, JTwig will process the administrator application output with Twig.

Default: No

#### Process Site

When enabled, JTwig will process the site application output with Twig.

Default: Yes

### onTwig events

* `onTwigResetData(object &$data)`
* `onTwigBeforeCreate(Twig_Loader_Array &$loader, array &$options)`
* `onTwigBeforeRender(Twig_Environment &$twig, array &$data)`
