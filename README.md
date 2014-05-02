Skeleton
========

A tiny templating engine, intended to be used to compile Tumblr themes. I find that managing a theme becomes difficult without any separation of concern within the theme file. This engine allows you to build your theme in different files and piece them together.

Here's how it works

### Getting Started

This repo contains everything you need to start with Skeleton. A basic setup of Skeleton can be found in the `compile` file. Just clone this repo and run `./compile` from bash.

To really use Skeleton, however, you should edit `compile`. It's a rather simple file, and shows all of the possible configurations Skeleton can have!

Although the included `compile` is basic, the most basic setup, however, would look like this:

```
//our base template is a file called base_template.html
$tumblrTheme = new View('base_template');

//tell Skeleton where to find files with certain extensions
//this means Skeleton will look for templates/base_template.html
$tumblrTheme->compileFrom([
	'html' => 'templates/'
]);

$tumblrTheme->compile();
```

### Skeleton resolves a template's dependencies.

For Tumblr, that's (pretty much) all you really need. 
Say we have a `main.html` file:

```
@require('foo.bar')
```

Skeleton will attempt to find a template called `foo/bar.html`. Check out the existing `templates/Skeleton.html` and run `./compile` from your terminal to see for yourself how this works.

### Skeleton can poll a series of source directories for changes.

This is the coolest part of Skeleton. It will pull any directories you indicate with `watch()` and scan all the files of those directories (and all sub-directories) for changes.

On detecting a change, Skeleton will compile your code and copy the end result to your clipboard, as well as saving the output to a file you designate with `compileTo()`.

### Thank you, and happy coding!

Email questions or comments to: [williamstein92@gmail.com](mailto:williamstein92@gmail.com)
