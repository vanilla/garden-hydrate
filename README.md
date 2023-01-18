# Garden Hydrate

*A JSON based templating language for data.*

[![CI](https://github.com/vanilla/garden-hydrate/actions/workflows/ci.yml/badge.svg)](https://github.com/vanilla/garden-hydrate/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/vanilla/garden-hydrate.svg?style=flat)](https://packagist.org/packages/vanilla/garden-hydrate)
![MIT License](https://img.shields.io/packagist/l/vanilla/garden-hydrate.svg?style=flat)
[![CLA](https://cla-assistant.io/readme/badge/vanilla/garden-hydrate)](https://cla-assistant.io/vanilla/garden-hydrate)

In computer science, data hydration involves taking an object that exists in memory, that doesn't yet contain any domain data ("real" data), and then populating it with domain data (such as from a database, from the network, or from a file system). Data hydration can take many forms and often requires custom logic that can be repetitive and tedious to write. Entry Garden Hydrate.

Garden Hydrate lets you define smart, but simple JSON data structures that are then transformed at runtime. It's sort of like a templating language for JSON.

Most work is done with the `DataHydrator` class. You call `resolve` and provide it a spec, and an optional parameter array. The hyrdrator then parses the spec, transforming it into the result.

## Using the DataHydrator Class

Here is a super basic example that shows you how hydrate works.

```php
$spec = [
    '$hydrate' => 'sprintf',
    'format' => 'Hello %s',
    'args' => [
        'World'
    ]
];

$hydrator = new DataHydrator();
$result = $hydrator->resolve($spec);

// $result will be 'Hello World'
```

You can see the special `$hydrate` key. Whenever the hydrator sees that key it looks for a resolver that will decide how to resolve the array. The above example uses the built in `sprintf` resolver that just calls `sprintf()` with arguments supplied in other keys.

Let's expand on the above example to use another built in resolver.

```php
$spec = [
    '$hydrate' => 'sprintf',
    'format' => 'Hello %s',
    'args' => [
        [
            '$hydrate' => 'param',
            'ref' => 'who'
        ]
    ]
];

$hydrator = new DataHydrator();
$result = $hydrator->resolve($spec, ['who' => 'Foo']);

// $result will be 'Hello Foo'
```

The `param` resolver lets you reference parameters passed to the spec. This is a good way to make use of the query string or a controller result. If you want to access nested parameters then you separate nested keys with the `"/"` character. The ref adheres to the [JSON reference](https://tools.ietf.org/id/draft-pbryan-zyp-json-ref-03.html) standard.  

## The Built-in Resolvers

By default, the following resolvers are provided with the `DataHydrator` class.

### literal

Resolve to a literal value under the `data` key. Useful when you want to use the reserved `@hyrdrate` key.

#### Example

```json
{
  "@hyrdrate": "literal",
  "data": {
    "$hydrate": "literal",
    "data": "Nothing here will get resolved."
  }
}
```

### param

Resolve to a parameter with the `ref` key. The value of `ref` should be a JSON reference.

#### Example

```json
{
  "$hydrate": "param",
  "ref": "path/to/key"
}
```

### ref

Resolve to a reference within the current resolved spec. The reference is in the `ref` key and should be a JSON reference.

```json
{
  "$hydrate": "ref",
  "ref": "/path/to/key"
}
```

You can use references to resolve to a value that gets hydrated earlier. However, be careful about resolution order. You can't reference something later in the spec because it will not be resolved when the reference is being resolved.

### sprintf

Call `sprintf()` on the node. The node uses the `format` key and the `args` key for the function arguments.

#### Example

```json
{
    "$hydrate": "sprintf",
    "format": "Hello %s",
    "args": [
    	"World"
    ]
}
```

## Adding Your Own Resolvers

The `DataHydrator` class doesn't provide much functionality with its built-in resolvers. To really unlock the power of the library you will want to add your own resolvers. To do so follow these steps:

1. Make a class that implements the `DataResolverInterface` interface. You need to implement a single `resolve()` method.
2. Optionally implement the `ValidatableResolverInterface` if you want your resolver to validate its spec before resolution. You need to implement a single `validate()` method. This recommended for providing a good developer experience.
3. Register your resolver using `DataHydrator::addResolver()`.
4. Reference your resolver by name the same as any other resolver with the `@hyrdate` key.

### Example

Let's take an example where we want to have an `lcase` resolver to lowercase strings. Garden Hydrate provides a nifty `FunctionResolver` helper class to help you map any callable to a resolver using reflection.

```php
use Garden\Hydrate\Resolvers\FunctionResolver;
use \Garden\Hydrate\DataHydrator;

$hydrator = new DataHydrator();
$lcase = new FunctionResolver(function (string $string) {
  return strtolower($string);
});
$hydrator->addResolver($lcase);

$r = $hydrator->resolve([
  '@hydrate' => 'lcase',
  'string' => 'STOP YELLING'
]);
// $r will be "stop yelling"
```

*Note: You could have also passed `'strtolower'` directly to the `FunctionResolver` constructor rather than wrapping it in a closure.* 

## Handling Exceptions

By default, if there is an exception then it will be thrown. This means that a single exception will wreck the entire hydration. This is often not desirable as you may want to recover from an exception in order display a useful message to the user.

The `DataHydrator` class lets you completely customize the behavior of exceptions that occur during hydration by registering your own exception handler with the `DataHydrator::setExceptionHandler()` method. To do so follow these steps:

1. Implement the `ExceptionHandlerInterface` to make your exception handler.
2. Register the exception handler with the `DataHydrator::setExceptionHandler()` method.
3. Your exception handler will be called whenever there is an exception along with the node that caused the exception and the exception that was thrown. You can then return corrected data or re-throw the exception.

### Example

Exception handling can be better understood with a concrete example. Let's say your spec represents a widget system that will be passed to a view layer to render one or more widgets. Each widget is defined by a `$widget` key that has the name of the widget type with parameters defined in other keys.

In this case you will want to render successful widgets and only display an error where it occurred to prevent a single widget from killing the entire page. In this case we can make a custom exception handler that will replace the widget with a generic error widget that will render an error message.

```php
class WidgetExceptionHandler implements ExceptionHandlerInterface {
	public function handleException(\Throwable $ex, array $data, array $params) {
    if (isset($data['$widget'])) {
      // If we are on a node that represents a widget then replace that widget with an error widget.
      return ['$widget' => 'error-message', 'message' => $ex->getMessage()];
    } else {
      // This isn't a widget node. Best to throw the exception to be caught by a parent widget node.
      throw $ex;
    }
  }
}
```

You can see in this example that your exception handler is called on each parent node until the exception is handled or you run out of parent nodes. In this way you can decide where to handle the exception and how.

Generally, you want to decide on acceptable error boundaries in your data and handle the exceptions there. The widget example above is a really common one. Here are some other examples:

- Maybe you are marking up a [JSON RSS Feed](https://www.jsonfeed.org/) and you want to make sure that errors are displayed as news items so that the feed still displays properly.
- Maybe you want to implement a poor man's [GraphQL](https://graphql.org/) where one or more API calls are represented in a JSON array. If one API call fails you want it to return an inline error message in place of the API result. You could implement this in your API client, but if you have many clients then you may want to use a custom exception handler.

## Middleware

*The middleware implementation is very much beta and subject to change. Consider it unsupported for now as it is subject to change.*

Middleware is an important feature that allows you to programmatically control the behavior of hydration in order to implement support for things like caching, logging, data transformation, debugging add-ons, etc. There could be any number of different domain specific implementations of these facilities, so it's better to provide a mechanism to add them rather than add those features in a way that may not be desirable to a specific implementation.

To write middleware, you create a class that implements the `MiddlewareInterface` and then register it with the `DataHydrator::addMiddleware()` method. The middleware contains one method: `process()`. It is passed a data the data from the node you want to process, the parameters passed to `resolve()` and the `$next` resolver you are responsible for calling.

If you are familiar with middleware, then the `$next` parameter should be familiar. If not, it will resolve the data. It is passed as a `DataResolverInterface` so that you can control when your middleware executes.

- If you want to augment the data before it is resolved then modify the `$data` or `$params` then call `$next->resolve()`.
- If you want to augment the result then call `$next->resolve()` then make your middleware do its thing.
- If you want to do something instead of processing the node then don't call `$next->resolve()` at all. This is how caching is commonly implemented.

Sometimes your middleware is configured globally at instantiation, and sometimes you want to configure it based on data passed in the transformation. If you want to configure the middleware on the data then you should read the middleware from the `$middleware` key on the data. The convention is that you define a key with your middleware's name and then put the parameters there:

```json5
{
  "$middleware": {
    "middleware-name": {"param1":  "value1", "param2": "Value2", /* ... */ },
    // ...
  }
}
```

Your middleware would be responsible for reading its configuration and acting on it. If it doesn't apply to the node then just return `$next->resolve()`.

Middleware is a very powerful paradigm that can add great functionality to the hydrator. Just be careful that your middleware is robust. It should generally always call `$next->resolve()` and return that result unless you specifically don't want to. If you don't call `$next->resolve()` then the node won't resolve at all.

### The `transform` Middleware

The `transform` middleware is used to tranform the resolved data on the node using a [Garden JSONT](https://github.com/vanilla/garden-jsont) spec. You can apply it like so:

```json5
{
  "$middleware": {
    "transform": { "key": "json ref", /* ... */ },
  }
}
```

This is a handy way to tidy up some slightly off spec API output to match a standardized format. Currently, you cannot use the `$hydrate` keyword within the `$middleware` key, but I could be persuaded to lift this restriction if I can be convinced it won't be abused ;)

 
## Case Studies

Following are a couple of case studies to illustrate the power most likely uses of Garden Hydrate.

### Data Localization

Let's say you are providing some static strings that will be displayed to the user. You may want to add the ability to translate those strings according to the user's selected locale. You can do this by registering your own translate resolver.

```json
{
  "$hydrate": "translate",
  "string": "Translation code"
}
```

Let's look at a basic example.

```json
{
  "title": {
    "$hydrate": "translate",
    "string": "Hello World"
  }
}
```

### Reading Configuration or User Preferences

Let's say you have a piece of data or parameter to another item that depends on a configuration setting or user preference. You can add a resolver that will read such settings in order to make use of them for other purposes.

*Warning! Wiring up config settings will most likely lead to a security vulnerability if you don't permission gate them properly. Consider nesting allowed settings under a single key or using some meta facility to ensure that sensitive information isn't exposed.*

### Utility Functions

You are probably going to want to wire up a slew of helpers and utility functions. Perhaps you want access to a greater share of PHP's standard library or perhaps you want to add some domain specific functionality to your hydration system.

### Poor Man's QraphQL

Let's say we want to implement the ability to wrap several API calls into a single call in order to reduce the round trips between the client and server. You decide to add a `POST /hydrate` endpoint that takes a Garden Hydrate spec and returns the result. In this case you are going to want to wire up your internal dispatcher to a resolver. Let's have a look at what the spec might look like here:

```json
{
  "$hydrate": "api",
  "path": "/resource/path",
  "query": {}
}
```

Let's see how this might look in practice:

```json
{
  "discussion": {
    "$hydrate": "api",
    "path": "/discussions/123"
  },
  "comments": {
    "$hydrate": "api",
    "path": "/comments",
    "query": {
      "discussionID": 123
    }
  }
}
```

If you consider using this implementation with some of the other case studies above you can really see the power and flexibility you can achieve with different combinations and nestings of hydrate specs. Wiring up your existing RESTful API is going to offer you 

*Note: We recommend supporting just `GET` requests on the endpoint to start.*
