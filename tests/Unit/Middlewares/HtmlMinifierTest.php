<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SmartCms\Kit\Http\Middlewares\HtmlMinifier;

it('can instantiate html minifier', function () {
    $minifier = new HtmlMinifier;

    expect($minifier)->toBeInstanceOf(HtmlMinifier::class);
});

it('removes html comments', function () {
    $minifier = new HtmlMinifier;

    $html = '<div><!-- This is a comment -->Content</div>';
    $minified = $minifier->minify($html);

    expect($minified)->not->toContain('<!-- This is a comment -->');
    expect($minified)->toContain('Content');
});

it('preserves php tags with space', function () {
    $minifier = new HtmlMinifier;

    $html = '<?php echo "test"; ?>';
    $minified = $minifier->minify($html);

    expect($minified)->toContain('<?php ');
});

it('removes carriage returns', function () {
    $minifier = new HtmlMinifier;

    $html = "<div>\r\nContent\r\n</div>";
    $minified = $minifier->minify($html);

    expect($minified)->not->toContain("\r");
});

it('removes newlines', function () {
    $minifier = new HtmlMinifier;

    $html = "<div>\nContent\n</div>";
    $minified = $minifier->minify($html);

    expect($minified)->not->toContain("\n");
});

it('removes tabs', function () {
    $minifier = new HtmlMinifier;

    $html = "<div>\t\tContent\t</div>";
    $minified = $minifier->minify($html);

    expect($minified)->not->toContain("\t");
});

it('condenses multiple spaces into single space', function () {
    $minifier = new HtmlMinifier;

    $html = '<div>     Content     </div>';
    $minified = $minifier->minify($html);

    expect($minified)->not->toContain('     ');
    expect($minified)->toContain(' ');
});

it('removes spaces between tags', function () {
    $minifier = new HtmlMinifier;

    $html = '<div> <span>Content</span> </div>';
    $minified = $minifier->minify($html);

    expect($minified)->toContain('><');
});

it('minifies complex html', function () {
    $minifier = new HtmlMinifier;

    $html = '
        <!-- Header -->
        <header>
            <nav>
                <ul>
                    <li>Item 1</li>
                    <li>Item 2</li>
                </ul>
            </nav>
        </header>
    ';

    $minified = $minifier->minify($html);

    expect($minified)->not->toContain("\n");
    expect($minified)->not->toContain("\t");
    expect($minified)->not->toContain('<!-- Header -->');
});

it('handles empty string', function () {
    $minifier = new HtmlMinifier;

    $minified = $minifier->minify('');

    expect($minified)->toBe('');
});

it('handles string with only whitespace', function () {
    $minifier = new HtmlMinifier;

    $minified = $minifier->minify("   \n\t   ");

    expect(strlen($minified))->toBeLessThan(10);
});

it('middleware passes request through without minification', function () {
    $middleware = new HtmlMinifier;

    $request = Request::create('/test');
    $response = new Response('<div>   Content   </div>');

    $result = $middleware->handle($request, function () use ($response) {
        return $response;
    });

    // Currently disabled (wire:ignore doesn't work with minification)
    // So it should return the original content
    expect($result->getContent())->toBe('<div>   Content   </div>');
});

it('preserves script tags content', function () {
    $minifier = new HtmlMinifier;

    $html = '<script>console.log("test");</script>';
    $minified = $minifier->minify($html);

    expect($minified)->toContain('<script>');
    expect($minified)->toContain('console.log("test");');
    expect($minified)->toContain('</script>');
});

it('preserves style tags content', function () {
    $minifier = new HtmlMinifier;

    $html = '<style>body { color: red; }</style>';
    $minified = $minifier->minify($html);

    expect($minified)->toContain('<style>');
    expect($minified)->toContain('body');
    expect($minified)->toContain('</style>');
});

it('handles nested tags correctly', function () {
    $minifier = new HtmlMinifier;

    $html = '
        <div>
            <section>
                <article>
                    <h1>Title</h1>
                    <p>Paragraph</p>
                </article>
            </section>
        </div>
    ';

    $minified = $minifier->minify($html);

    expect($minified)->toContain('<div>');
    expect($minified)->toContain('<section>');
    expect($minified)->toContain('<article>');
    expect($minified)->toContain('<h1>');
    expect($minified)->toContain('Title');
});
