<?php

use Rize\UriTemplate;
use Rize\UriTemplate\Node;

/**
 * URI Template
 *
 * http://tools.ietf.org/html/rfc6570
 */
class UriTemplateTest extends \PHPUnit_Framework_TestCase
{
    public function service($uri = '', $params = array())
    {
        return new UriTemplate($uri, $params);
    }

    public function dataExpansion()
    {
        $params = array(
            'count' => array("one", "two", "three"),
            'dom'  => array("example", "com"),
            'dub'  => "me/too",
            'hello' => "Hello World!",
            'half' => "50%",
            'var'  => "value",
            'who'  => "fred",
            'base' => "http://example.com/home/",
            'path' => "/foo/bar",
            'list' => array("red", "green", "blue"),
            'keys' => array(
                "semi" => ";",
                "dot"  => ".",
                "comma" => ",",
            ),
            'list_with_empty' => array(''),
            'keys_with_empty' => array('john' => ''),
            'v' => "6",
            'x' => "1024",
            'y' => "768",
            'empty' => "",
            'empty_keys' => array(),
            'undef' => null,
        );

        return array(

            array(
                'http://example.com/~john',
                array(
                    'uri'    => 'http://example.com/~{username}',
                    'params' => array(
                        'username' => 'john',
                    ),
                ),
            ),

            array(
                'http://example.com/dictionary/d/dog',
                array(
                    'uri'    => 'http://example.com/dictionary/{term:1}/{term}',
                    'params' => array(
                        'term' => 'dog',
                    ),
                    'extract' => array(
                        'term:1' => 'd',
                        'term'   => 'dog',
                    ),
                ),
            ),

            # Form-style parameters expression
            array(
                'http://example.com/j/john/search?q=mycelium&q=3&lang=th,jp,en',
                array(
                    'uri'    => 'http://example.com/{term:1}/{term}/search{?q*,lang}',
                    'params' => array(
                        'q'    => array('mycelium', 3),
                        'lang' => array('th', 'jp', 'en'),
                        'term' => 'john',
                    ),
                ),
            ),

            array(
                'http://www.example.com/john',
                array(
                    'uri'    => 'http://www.example.com/{username}',
                    'params' => array(
                        'username' => 'john',
                    ),
                ),
            ),

            array(
                'http://www.example.com/foo?query=mycelium&number=100',
                array(
                    'uri'    => 'http://www.example.com/foo{?query,number}',
                    'params' => array(
                        'query'  => 'mycelium',
                        'number' => 100,
                    ),
                ),
            ),

            # 'query' is undefined
            array(
                'http://www.example.com/foo?number=100',
                array(
                    'uri'    => 'http://www.example.com/foo{?query,number}',
                    'params' => array(
                        'number' => 100,
                    ),
                    # we can't extract undefined values
                    'extract' => false,
                ),
            ),

            # undefined variables
            array(
                'http://www.example.com/foo',
                array(
                    'uri'    => 'http://www.example.com/foo{?query,number}',
                    'params' => array(),
                    'extract' => array('query' => null, 'number' => null),
                ),
            ),

            array(
                'http://www.example.com/foo',
                array(
                    'uri'    => 'http://www.example.com/foo{?number}',
                    'params' => array(),
                    'extract' => array('number' => null),
                ),
            ),

            array(
                'one,two,three|one,two,three|/one,two,three|/one/two/three|;count=one,two,three|;count=one;count=two;count=three|?count=one,two,three|?count=one&count=two&count=three|&count=one&count=two&count=three',
                array(
                    'uri'    => '{count}|{count*}|{/count}|{/count*}|{;count}|{;count*}|{?count}|{?count*}|{&count*}',
                    'params' => array(
                        'count' => array('one', 'two', 'three'),
                    ),
                ),
            ),

            array(
                'http://www.host.com/path/to/a/file.x.y',
                array(
                    'uri'   => 'http://{host}{/segments*}/{file}{.extensions*}',
                    'params' => array(
                        'host' => 'www.host.com',
                        'segments' => array('path', 'to', 'a'),
                        'file' => 'file',
                        'extensions' => array('x', 'y'),
                    ),
                    'extract' => array(
                        'host' => 'www.host.com',
                        'segments' => array('path', 'to', 'a'),
                        'file' => 'file.x.y',
                        'extensions' => null,
                    ),
                ),
            ),

            # level 1 - Simple String Expansion: {var}
            array(
                'value|Hello%20World%21|50%25|OX|OX|1024,768|1024,Hello%20World%21,768|?1024,|?1024|?768|val|value|red,green,blue|semi,%3B,dot,.,comma,%2C|semi=%3B,dot=.,comma=%2C',
                array(
                    'uri'    => '{var}|{hello}|{half}|O{empty}X|O{undef}X|{x,y}|{x,hello,y}|?{x,empty}|?{x,undef}|?{undef,y}|{var:3}|{var:30}|{list}|{keys}|{keys*}',
                    'params' => $params,
                ),
            ),

            # level 2 - Reserved Expansion: {+var}
            array(
                'value|Hello%20World!|50%25|http%3A%2F%2Fexample.com%2Fhome%2Findex|http://example.com/home/index|OX|OX|/foo/bar/here|here?ref=/foo/bar|up/foo/barvalue/here|1024,Hello%20World!,768|/foo/bar,1024/here|/foo/b/here|red,green,blue|red,green,blue|semi,;,dot,.,comma,,|semi=;,dot=.,comma=,',
                array(
                    'uri'    => '{+var}|{+hello}|{+half}|{base}index|{+base}index|O{+empty}X|O{+undef}X|{+path}/here|here?ref={+path}|up{+path}{var}/here|{+x,hello,y}|{+path,x}/here|{+path:6}/here|{+list}|{+list*}|{+keys}|{+keys*}',
                    'params' => $params,
                ),
            ),

            # level 2 - Fragment Expansion: {#var}
            array(
                '#value|#Hello%20World!|#50%25|foo#|foo|#1024,Hello%20World!,768|#/foo/bar,1024/here|#/foo/b/here|#red,green,blue|#red,green,blue|#semi,;,dot,.,comma,,|#semi=;,dot=.,comma=,',
                array(
                    'uri'    => '{#var}|{#hello}|{#half}|foo{#empty}|foo{#undef}|{#x,hello,y}|{#path,x}/here|{#path:6}/here|{#list}|{#list*}|{#keys}|{#keys*}',
                    'params' => $params,
                ),
            ),

            # Label Expansion with Dot-Prefix: {.var}
            array(
                '.fred|.fred.fred|.50%25.fred|www.example.com|X.value|X.|X|X.val|X.red,green,blue|X.red.green.blue|X.semi,%3B,dot,.,comma,%2C|X.semi=%3B.dot=..comma=%2C|X|X',
                array(
                    'uri'    => '{.who}|{.who,who}|{.half,who}|www{.dom*}|X{.var}|X{.empty}|X{.undef}|X{.var:3}|X{.list}|X{.list*}|X{.keys}|X{.keys*}|X{.empty_keys}|X{.empty_keys*}',
                    'params' => $params,
                ),
            ),

            # Path Segment Expansion: {/var}
            array(
                '/fred|/fred/fred|/50%25/fred|/fred/me%2Ftoo|/value|/value/|/value|/value/1024/here|/v/value|/red,green,blue|/red/green/blue|/red/green/blue/%2Ffoo|/semi,%3B,dot,.,comma,%2C|/semi=%3B/dot=./comma=%2C',
                array(
                    'uri'    => '{/who}|{/who,who}|{/half,who}|{/who,dub}|{/var}|{/var,empty}|{/var,undef}|{/var,x}/here|{/var:1,var}|{/list}|{/list*}|{/list*,path:4}|{/keys}|{/keys*}',
                    'params' => $params,
                ),
            ),

            # Path-Style Parameter Expansion: {;var}
            array(
                ';who=fred|;half=50%25|;empty|;v=6;empty;who=fred|;v=6;who=fred|;x=1024;y=768|;x=1024;y=768;empty|;x=1024;y=768|;hello=Hello|;list=red,green,blue|;list=red;list=green;list=blue|;keys=semi,%3B,dot,.,comma,%2C|;semi=%3B;dot=.;comma=%2C',
                array(
                    'uri'    => '{;who}|{;half}|{;empty}|{;v,empty,who}|{;v,bar,who}|{;x,y}|{;x,y,empty}|{;x,y,undef}|{;hello:5}|{;list}|{;list*}|{;keys}|{;keys*}',
                    'params' => $params,
                ),
            ),

            # Form-Style Query Expansion: {?var}
            array(
                '?who=fred|?half=50%25|?x=1024&y=768|?x=1024&y=768&empty=|?x=1024&y=768|?var=val|?list=red,green,blue|?list=red&list=green&list=blue|?keys=semi,%3B,dot,.,comma,%2C|?semi=%3B&dot=.&comma=%2C|?list_with_empty=|?john=',
                array(
                    'uri'    => '{?who}|{?half}|{?x,y}|{?x,y,empty}|{?x,y,undef}|{?var:3}|{?list}|{?list*}|{?keys}|{?keys*}|{?list_with_empty*}|{?keys_with_empty*}',
                    'params' => $params,
                ),
            ),

            # Form-Style Query Continuation: {&var}
            array(
                '&who=fred|&half=50%25|?fixed=yes&x=1024|&x=1024&y=768&empty=|&x=1024&y=768|&var=val|&list=red,green,blue|&list=red&list=green&list=blue|&keys=semi,%3B,dot,.,comma,%2C|&semi=%3B&dot=.&comma=%2C',
                array(
                    'uri'    => '{&who}|{&half}|?fixed=yes{&x}|{&x,y,empty}|{&x,y,undef}|{&var:3}|{&list}|{&list*}|{&keys}|{&keys*}',
                    'params' => $params,
                ),
            ),

            # Test empty values
            array(
                '||?empty=|?empty=',
                array(
                    'uri'   => '{empty}|{empty*}|{?empty}|{?empty*}',
                    'params' => array(
                        'empty' => array(),
                    ),
                ),
            ),
        );
    }

    public function dataBaseTemplate()
    {
        return array(
            array(
                'http://google.com/api/1/users/1',
                # base uri
                array(
                    'uri' => '{+host}/api/{v}',
                    'params' => array(
                        'host' => 'http://google.com',
                        'v'    => 1,
                    ),
                ),
                # other uri
                array(
                    'uri' => '/{resource}/{id}',
                    'params' => array(
                        'resource' => 'users',
                        'id'       => 1,
                    ),
                ),
            ),

            # test override base params
            array(
                'http://github.com/api/1/users/1',
                # base uri
                array(
                    'uri' => '{+host}/api/{v}',
                    'params' => array(
                        'host' => 'http://google.com',
                        'v'    => 1,
                    ),
                ),
                # other uri
                array(
                    'uri' => '/{resource}/{id}',
                    'params' => array(
                        'host'     => 'http://github.com',
                        'resource' => 'users',
                        'id'       => 1,
                    ),
                ),
            ),
        );
    }

    public function dataExtraction()
    {
        return array(
            array(
                '/no/{term:1}/random/{term}/{test*}/foo{?query,number}',
                '/no/j/random/john/a,b,c/foo?query=1,2,3&number=10',
                array(
                    'term:1' => 'j',
                    'term'   => 'john',
                    'test'   => array('a', 'b', 'c'),
                    'query'  => array(1, 2, 3),
                    'number' => 10,
                ),
            ),
            array(
                'http://www.example.com/foo{?query,number}',
                'http://www.example.com/foo?query=5',
                array(
                    'query'  => null,
                    'number' => null,
                ),
            ),
            array(
                '{count}|{count*}|{/count}|{/count*}|{;count}|{;count*}|{?count}|{?count*}|{&count*}',
                'one,two,three|one,two,three|/one,two,three|/one/two/three|;count=one,two,three|;count=one;count=two;count=three|?count=one,two,three|?count=one&count=two&count=three|&count=one&count=two&count=three',
                array(
                    'count' => array('one', 'two', 'three'),
                ),
            ),
            array(
                'http://example.com/{term:1}/{term}/search{?q*,lang}',
                'http://example.com/j/john/search?q=mycelium&q=3&lang=th,jp,en',
                array(
                    'q'      => array('mycelium', 3),
                    'lang'   => array('th', 'jp', 'en'),
                    'term'   => 'john',
                    'term:1' => 'j',
                ),
            ),
        );
    }

    /**
     * @dataProvider dataExpansion
     */
    public function testExpansion($expected, $input)
    {
        $service = $this->service();
        $result  = $service->expand($input['uri'], $input['params']);

        $this->assertEquals($expected, $result);
    }

    public function _testParseExpression()
    {
        $service = $this->service();
        $result  = $service->parseExpression('+test,b', array('test' => 'ab/c'));

        $this->assertEquals('ab/c', $result);
    }

    /**
     * @dataProvider dataBaseTemplate
     */
    public function testBaseTemplate($expected, $base, $other)
    {
        $service  = $this->service($base['uri'], $base['params']);
        $result   = $service->expand($other['uri'], $other['params']);

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider dataExtraction
     */
    public function testExtract($template, $uri, $expected)
    {
        $service = $this->service();
        $actual  = $service->extract($template, $uri);

        $this->assertEquals($expected, $actual);
    }

}
