<?php

namespace SouthCoast\SouthWest\Component;

use SouthCoast\Helpers\ArrayHelper;
use SouthCoast\SouthWest\Core\Request;

/**
 * The Route Component
 * Holds all route related data
 *
 * @todo Add RouteError class
 */
class Route
{
    /**
     * The Required properties for this object
     *
     * @var array
     */
    const REQUIRED_PROPERTIES = [
        'id',
        'type',
        'route',
        'match_pattern',
        'request_method',
        'variables',
        'handler',
    ];

    /**
     * The Unique identifier for this route
     *
     * @var string GUID
     */
    public $id;

    /**
     * The route type (e.g. ControllerAction || RedirectAction)
     *
     * @var string
     */
    public $type;

    /**
     * The raw Route Array
     *
     * @var array
     */
    public $route;

    /**
     * The Route's Match Pattern (REGEX)
     * Used for matching the request path to the route
     *
     * @var string Regex
     */
    public $match_pattern;

    /**
     * The Accepted Request Methods
     *
     * @var array
     */
    public $request_method;

    /**
     * The keys of the route variables that need to be passed onto the controller
     *
     * @var array
     */
    public $variables;

    /**
     * The handler for this route
     * Can be a class or URL. Depends on the route type
     *
     * @var array|string
     */
    public $handler;

    public function __construct(array $data)
    {
        if (!ArrayHelper::requiredPramatersAreSet(self::REQUIRED_PROPERTIES, $data, $missing, true)) {
            /* TODO: Trow Route Error RouteError::MISSING_PROPERTIES */
        }

        $this->load($data);
    }

    protected function load(array $data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function getController()
    {
        $controller = $this->handler['controller'];
        return new $controller();
    }

    public function getMethod(): string
    {
        return $this->handler['method'];
    }

    public function getVariableValues(): array
    {
        $tmp = [];
        $variable_keys = array_merge($this->variables['required'], $this->variables['optional']);
        foreach ($variable_keys as $index) {
            $tmp[] = Request::$path_array[$index] ?? null;
        }
        return $tmp;
    }

    public function getPattern(): string
    {
        return $this->match_pattern;
    }

    public function matches(string $path): bool
    {
        return preg_match($this->match_pattern, $path) ? true : false;
    }

    public function hasAccess()
    {
        $access_rules = ($this->handler['controller'] . '::_access')();

        return $access_rules[$this->handler['method']] ?? true;
    }

    public function acceptsRequestMethod(string $method)
    {
        return (in_array($method, $this->request_method));
    }

}
