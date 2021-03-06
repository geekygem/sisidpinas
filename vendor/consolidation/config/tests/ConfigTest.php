<?php
namespace Consolidation\Config;

use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testSetters()
    {
        // Pointless tests just to ensure everything is covered.
        $config = new Config();
        $config->set('foo', 'bar');
        $data = $config->export();
        $this->assertEquals('{"foo":"bar"}', json_encode($data));
    }

    public function testCombine()
    {
        // Pointless tests just to ensure everything is covered.
        $config = new Config();
        $config->set('foo', 'bar');
        $config->set('baz', 'boz');
        $config2 = new Config();
        $config2->set('foo', 'fu');
        $config2->set('new', 'blue');
        $config->combine($config2->export());
        $this->assertEquals('fu', $config->get('foo'));
        $this->assertEquals('boz', $config->get('baz'));
        $this->assertEquals('blue', $config->get('new'));
    }

    public function testDefault()
    {
        $data = [
            'a' => 'foo',
            'b' => 'bar',
            'c' => 'boz',
        ];

        $config = new Config($data);

        $config->setDefault('c', 'other');
        $config->setDefault('d', 'other');

        $this->assertEquals('foo', $config->get('a'));
        $this->assertEquals('boz', $config->get('c'));
        $this->assertEquals('other', $config->get('d'));
        $this->assertEquals('other', $config->getDefault('c'));
        $this->assertEquals('', $config->get('e'));
    }

    public function testConfigurationWithCrossFileReferences()
    {
        $config = new Config();
        $processor = new ConfigProcessor();
        $loader = new YamlConfigLoader();
        $processor->extend($loader->load(__DIR__ . '/data/config-1.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-2.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-3.yml'));

        // Does not fail if configuration file cannot be found
        $processor->extend($loader->load(__DIR__ . '/data/no-such-file.yml'));

        // We must capture the sources before exporting, as export
        // dumps this information.
        $sources = $processor->sources();

        $config->import($processor->export());

        $this->assertEquals(implode(',', $config->get('m')), '3');
        $this->assertEquals($config->get('a'), 'foobarbaz');

        $this->assertEquals($sources['a'], __DIR__ . '/data/config-3.yml');
        $this->assertEquals($sources['b'], __DIR__ . '/data/config-2.yml');
        $this->assertEquals($sources['c'], __DIR__ . '/data/config-1.yml');
    }

    public function testConfigurationWithReverseOrderCrossFileReferences()
    {
        $config = new Config();
        $processor = new ConfigProcessor();
        $loader = new YamlConfigLoader();
        $processor->extend($loader->load(__DIR__ . '/data/config-3.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-2.yml'));
        $processor->extend($loader->load(__DIR__ . '/data/config-1.yml'));

        $sources = $processor->sources();
        $config->import($processor->export());

        $this->assertEquals(implode(',', $config->get('m')), '1');

        if (strpos($config->get('a'), '$') !== false) {
            throw new \PHPUnit_Framework_SkippedTestError(
                'Evaluation of cross-file references in reverse order not supported.'
            );
        }
        $this->assertEquals($config->get('a'), 'foobarbaz');

        $this->assertEquals($sources['a'], __DIR__ . '/data/config-3.yml');
        $this->assertEquals($sources['b'], __DIR__ . '/data/config-2.yml');
        $this->assertEquals($sources['c'], __DIR__ . '/data/config-1.yml');
    }
}
