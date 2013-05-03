<?php
namespace Elicorege;

use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Version\VersionParser;
use Composer\Json\JsonFile;
use Composer\Package\Dumper\ArrayDumper;

class Generator
{
    protected $name;

    protected $homepage;

    protected $base_local_path;

    protected $base_clone_path;

    protected $repos;

    protected $output_dir;

    public function __construct($opts = array())
    {
        if (empty($opts['config-file']) || !is_file($opts['config-file'])) {
            throw new Exception('missing --config-file or file not exists');
        }
        if (empty($opts['output-dir']) || !is_dir($opts['output-dir'])) {
            throw new Exception('missing --output-dir or directory not exists');
        }

        $config = json_decode(file_get_contents($opts['config-file']), true);
        
        if ($config === null) {
            throw new Exception('config file cannot be decoded, malformed json');
        } else if (empty($config)) {
            throw new Exception('config file cannot be read');
        }

        $this->name = $config['name'];
        $this->homepage = $config['homepage'];
        $this->base_local_path = rtrim($config['base_local_path'], '/');
        $this->base_clone_path = $config['base_clone_path'];
        $this->repos = $config['repos'];

        $this->output_dir = rtrim($opts['output-dir'], '/');
    }

    public function run()
    {
        $this->msg('CD packages list update');

        $this->msg('reading list of packages');
       
        $packages = array();

        foreach ($this->repos as $repo_name => $repo) {
            
            if (empty($repo['references']) || !is_array($repo['references'])) {
                $this->msg(sprintf('- %s | no references defined', $repo['relative_path']));
                continue;
            }
            
            foreach ($repo['references'] as $reference) {
                $this->msg(sprintf('- %s | %s | ', $repo['relative_path'], $reference), false);
                
                $package = $this->readPackage($repo['relative_path'], $reference);
                if ($package) {
                    $this->msg(sprintf('%s (%s)', $package->getPrettyName(), $package->getSourceReference()));
                    $packages[] = $package;                    
                }
            }
        }

        $this->dumpJson($packages);
        $this->dumpHtml($packages);
    
        $this->msg(sprintf('done, see %s', $this->homepage));
    }

    /**
     * @return \Composer\Package\Package
     */
    public function readPackage($relative_path, $reference)
    {
        $path = sprintf("%s/%s", $this->base_local_path, $relative_path);

        $output = $this->command(sprintf("git --git-dir=%s show %s:composer.json", $path, $reference), $return_var);

        if ($return_var !== 0) {
            return false;
        }

        $config = JsonFile::parseJson($output);

        if (!isset($config['version'])) {
            if ($reference == 'master') {
                $config['version'] = 'dev-master';
            } else {
                $config['version'] = $reference;
            }
        }

        $config['source'] = array(
            'type' => 'git',
            'url' => sprintf('%s:%s', $this->base_clone_path, $relative_path),
            'reference' => $config['version']
        );

        if ($config['version'] == 'dev-master') {
            $hash = $this->command(sprintf("git --git-dir=%s rev-parse master", $path), $return_var);
            $config['source']['reference'] = $hash;
        }

        if (!isset($config['time'])) {
            $raw_date = $this->command(sprintf("git --git-dir=%s log -1 --format='%%ci' %s", $path, $config['source']['reference']), $return_var);
            if ($raw_date) {
                $date = date_create($raw_date);
                date_timezone_set($date, timezone_open('GMT'));
                $config['time'] = $date->format('Y-m-d H:i:s');
            }
        }
        
        $loader = new ArrayLoader(new VersionParser());
        $package = $loader->load($config);

        return $package;
    }

    protected function dumpJson(array $packages)
    {
        $repo = array('packages' => array());
        $dumper = new ArrayDumper;

        foreach ($packages as $package) {
            $repo['packages'][$package->getPrettyName()][$package->getPrettyVersion()] = $dumper->dump($package);
        }

        $this->msg('writing packages.json');
        $filename = sprintf('%s/packages.json', $this->output_dir);
        $file = new JsonFile($filename);
        $file->write($repo);
    }

    protected function dumpHtml(array $packages)
    {
        $this->msg('writing index.html');

        $template_dir = __DIR__.'/../../views';
        $loader = new \Twig_Loader_Filesystem($template_dir);
        $twig = new \Twig_Environment($loader);

        $grouped_packages = array();

        foreach ($packages as $package) {
            $name = $package->getName();

            if (!isset($grouped_packages[$name])) {
                $grouped_packages[$name] = array(
                    'id' => str_replace('/', '_', $name),
                    'meta' => $package,
                    'packages' => array()
                );
            }
            
            $grouped_packages[$name]['packages'][] = $package;
        }

        ksort($grouped_packages);

        $content = $twig->render('index.html.twig', array(
            'name' => $this->name,
            'url' => $this->homepage,
            'meta' => $package,
            'grouped_packages' => $grouped_packages
        ));

        file_put_contents(sprintf('%s/index.html', $this->output_dir), $content);
    }

    protected function command($command, &$return_var)
    {
        ob_start();
        system($command, $return_var);
        return trim(ob_get_clean());
    }

    protected function msg($text, $new_line = true)
    {
        echo sprintf('%s%s', $text, $new_line ? PHP_EOL : '');
    }
}

