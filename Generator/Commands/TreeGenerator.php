<?php


namespace Apiato\Core\Generator\Commands;


use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Symfony\Component\Console\Input\InputOption;

class TreeGenerator extends GeneratorCommand implements ComponentsGenerator
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'apiato:generate:tree';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Tree files for apiato from scratch (API Part)';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $fileType = 'Container';

    /**
     * The structure of the file path.
     *
     * @var  string
     */
    protected $pathStructure = '{container-name}/*';

    /**
     * The structure of the file name.
     *
     * @var  string
     */
    protected $nameStructure = '{file-name}';

    /**
     * The name of the stub file.
     *
     * @var  string
     */
    protected $stubName = 'composer.stub';

    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads whenever it's called".
     *
     * @var  array
     */
    public $inputs = [
        ['docversion', null, InputOption::VALUE_OPTIONAL, 'The version of all endpoints to be generated (1, 2, ...)'],
        ['doctype', null, InputOption::VALUE_OPTIONAL, 'The type of all endpoints to be generated (private, public)'],
        ['url', null, InputOption::VALUE_OPTIONAL, 'The base URI of all endpoints (/stores, /cars, ...)'],
    ];

    /**
     * @return array
     */
    public function getUserInputs()
    {
        $ui = 'api';

        // containername as inputted and lower
        $containerName = $this->containerName;
        $_containerName = Str::lower($this->containerName);

        // name of the model (singular and plural)
        $model = $this->containerName;
        $models = Str::plural($model);

        // create a transformer for the model
        $this->printInfoMessage('Generating Transformer for the Model');
        $this->call('apiato:generate:transformer', [
            '--container'   => $containerName,
            '--file'        => $containerName . 'TreeTransformer',
            '--model'       => $model,
            '--full'        => false,
        ]);

        // create the default routes for this container
        $this->printInfoMessage('Generating Default Routes');
        $version = $this->checkParameterOrAsk('docversion', 'Enter the version for *all* API endpoints (integer)', 1);
        $doctype = $this->checkParameterOrChoice('doctype', 'Select the type for *all* endpoints', ['private', 'public'], 1);

        // get the URI and remove the first trailing slash
        $url = Str::lower($this->checkParameterOrAsk('url', 'Enter the base URI for all endpoints (foo/bar/tree/{id?})', Str::lower($models).'/tree/{id?}'));
        $url = ltrim($url, '/');

        $this->printInfoMessage('Creating Requests for Routes');
        $this->printInfoMessage('Generating Default Actions');
        $this->printInfoMessage('Generating Default Tasks');

        $route = [
            'stub'        => 'GetTree',
            'name'        => 'Get' . $models . 'Tree',
            'operation'   => 'get' . $models . 'Tree',
            'verb'        => 'GET',
            'url'         => $url,
            'controller'  => 'TreeController',
            'action'      => 'Get' . $models . 'TreeAction',
            'request'     => 'GetAll' . $models . 'TreeRequest',
            'task'        => 'GetAll' . $models . 'TreeTask',
        ];

        $this->call('apiato:generate:route', [
            '--container' => $containerName,
            '--file' => $route['name'],
            '--ui' => $ui,
            '--operation' => $route['operation'],
            '--doctype' => $doctype,
            '--docversion' => $version,
            '--url' => $route['url'],
            '--verb' => $route['verb'],
            '--controller' => $route['controller'],
        ]);

        $this->call('apiato:generate:request', [
            '--container' => $containerName,
            '--file' => $route['request'],
            '--ui' => $ui,
        ]);

        $this->call('apiato:generate:action', [
            '--container' => $containerName,
            '--file' => $route['action'],
            '--model' => $model,
            '--stub' => $route['stub'],
        ]);

        $this->call('apiato:generate:task', [
            '--container' => $containerName,
            '--file' => $route['task'],
            '--model' => $model,
            '--stub' => $route['stub'],
        ]);

        // finally generate the controller
        $this->printInfoMessage('Generating Controller to wire everything together');
        $this->call('apiato:generate:controller', [
            '--container'   => $containerName,
            '--file'        => 'TreeController',
            '--ui'          => $ui,
            '--stub'        => 'tree.' . $ui,
        ]);

        $this->printInfoMessage('Generating Composer File');
        return [
            'path-parameters' => [
                'container-name' => $containerName,
            ],
            'stub-parameters' => [
                '_container-name' => $_containerName,
                'container-name' => $containerName,
                'class-name' => $this->fileName,
            ],
            'file-parameters' => [
                'file-name' => $this->fileName,
            ],
        ];
    }

    /**
     * Get the default file name for this component to be generated
     *
     * @return string
     */
    public function getDefaultFileName()
    {
        return 'composer';
    }

    public function getDefaultFileExtension()
    {
        return 'json';
    }

}
