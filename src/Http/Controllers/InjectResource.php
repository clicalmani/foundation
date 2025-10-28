<?php
namespace Clicalmani\Foundation\Http\Controllers;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Exceptions\ModelNotFoundException;
use Clicalmani\Foundation\Http\Request;
use Clicalmani\Validation\AsValidator;

class InjectResource extends InjectionLocator
{
    private array $resources = [];

    public function handle(): ?object
    {
        if (is_subclass_of($this->class, \Clicalmani\Database\Factory\Models\Elegant::class)) {

            $this->resources = $this->bindResources();

            return @$this->resources[0];
        }
        
        return null;
    }

    public function inject(array &$args, mixed $value, int $pos): void
    {
        $args[$pos] = $value;

        if ($arr = $this->reflector->getNestedResource()) {
            $args[$arr['pos']] = $this->resources[1];
        }
    }

    /**
	 * Bind models resources
	 * 
	 * @return array
	 */
	private function bindResources() : array
    {
		$resource = $this->reflector->getResource()['name'];
		$nested_resource = @$this->reflector->getNestedResource()['name'];
		$request = Request::current();
		
		if ($this->reflector instanceof MethodReflector) {
			if ($attribute = (new \ReflectionMethod($this->reflector->getClass(), $this->reflector->getName()))->getAttributes(AsValidator::class)) {
				$request->merge($attribute[0]->newInstance()->args);
				Request::current($request);
			}
		}

		$model = null;
		$nested_model = null;
		
		// Check if resource is present
		if ( NULL !== $id = $request->id AND in_array($this->reflector->getName(), ['create', 'show', 'edit', 'update', 'destroy']) ) {
			
			/**
			 * Model record key value
			 * 
			 * @var string[]
			 */
			$key_value = explode(',', (string)$id);
			
			if ( count($key_value) ) {
				if ( count($key_value) === 1 ) $key_value = $key_value[0];	// Single primary key

				if ($scoped = $this->route->scoped()) {
					foreach ($scoped as $skey => $scope) {
						$key_class = "App\\Models\\" . collection(explode('_', $skey))->map(fn(string $part) => ucfirst($part))->join('');

						if ($key_class === $resource AND $model = $resource::where("$scope = ?", [$key_value])->first()) {
							break;
						}
					}
				}
				
				if (!isset($model)) {
					/** @var \Clicalmani\Database\Factory\Models\Elegant */
					$model = new $resource($key_value);
				}

				$this->resolveRouteBinding($model);

				if ( $model->get()->isEmpty() ) throw new ModelNotFoundException($resource);

			} else throw new ModelNotFoundException($resource);
		} else {
			/** @var \Clicalmani\Database\Factory\Models\Elegant */
			$model = new $resource;
		}

		// Check if nested resource is present
		if ( NULL !== $nid = $request->nid AND $nested_resource ) {
			/** 
			 * Nested model key value
			 * 
			 * @var string[] 
			 */
			$nested_key_value = explode(',', (string)$nid);

			if ( count($nested_key_value) ) {
				if ( count($nested_key_value) === 1 ) $nested_key_value = $nested_key_value[0];	// Single primary key

				if ($scoped = $this->route->scoped()) {
					foreach ($scoped as $skey => $scope) {
						$nested_key_class = "App\\Models\\" . collection(explode('_', $skey))->map(fn(string $part) => ucfirst($part))->join('');

						if ($nested_key_class === $nested_resource AND $nested_model = $nested_resource::where("$scope = ?", [$key_value])->first()) {
							break;
						}
					}
				}
				
				if (!isset($nested_model)) {
					/** @var \Clicalmani\Database\Factory\Models\Elegant */
					$nested_model = new $nested_resource($nested_key_value);
				}

				$this->resolveRouteBinding($nested_model);

				if ( $nested_model->get()->isEmpty() ) throw new ModelNotFoundException($nested_resource);

			} else throw new ModelNotFoundException($nested_resource);
		} else {
			/** @var \Clicalmani\Database\Factory\Models\Elegant|null */
			$nested_model = $nested_resource ? new $nested_resource: null;
		}

		/**
		 * Bind resources
		 */
		$this->bindRoutines($model);

		if ( NULL !== $nested_model ) $this->bindRoutines($nested_model);
		
		return [$model, $nested_model];
	}

    /**
	 * Resolve route binding
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $model
	 * @return void
	 */
	private function resolveRouteBinding(Elegant $model) : void
	{
		if ($scope = $this->route->scoped()) {
			$scope_name = collection(explode('_', $model))->map(fn(string $part) => ucfirst($part))->join('');
			$keyName = $scope[$scope_name];
		} else $keyName = $model->getKey();

		// Resolve route binding inside the model
		$reflector = new MethodReflector(new \ReflectionMethod($model, 'resolveRouteBinding'));
		$reflector($model, $model->{$keyName}, $keyName);

		// Global route binding
		if (NULL !== $callback = \App\Providers\RouteServiceProvider::routeBindingCallback())
			$callback($model->{$keyName}, $keyName, $model);
	}

    /**
	 * Bind resource routines
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function bindRoutines(Elegant $model) : void
	{
		/**
		 * Select distinct
		 */
		$this->getResourceDistinct($model);

		/**
		 * Insert ignore
		 */
		$this->createResourceIgnore($model);

		/**
		 * Delete multiple
		 */
		$this->resourceDeleteFrom($model);

		/**
		 * Pagination
		 */
		$this->resourceCalcRows($model);

		/**
		 * Limit rows
		 */
		$this->resourceLimit($model);

		/**
		 * Row order by
		 */
		$this->resourceOrderBy($model);
	}

    /**
	 * Distinct rows
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function getResourceDistinct(Elegant $obj) : void
	{
		if ( $distinct = $this->route?->distinctResult() ) {
			$obj->distinct($distinct);
		}
	}

	/**
	 * Ignore duplicates
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function createResourceIgnore(Elegant $obj) : void
	{
		if ( $ignore = $this->route?->ignoreKeyWarning() ) {
			$obj->ignore($ignore);
		}
	}

	/**
	 * Delete from
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function resourceDeleteFrom(Elegant $obj) : void
	{
		if ( $from = $this->route?->deleteFrom() ) {
			$obj->from($from);
		}
	}

	/**
	 * Calc rows
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function resourceCalcRows(Elegant $obj) : void
	{
		if ( $enable = $this->route?->calcFoundRows() ) {
			$obj->calcFoundRows($enable);
		}
	}

	/**
	 * Limit rows
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function resourceLimit(Elegant $obj) : void
	{
		if ( $arr = $this->route?->limitResult() ) {
			$obj->limit($arr['offset'], $arr['count']);
		}
	}

	/**
	 * Order by
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function resourceOrderBy(Elegant $obj) : void
	{
		if ( $order_by = $this->route?->orderResultBy() ) {
			$obj->orderBy($order_by);
		}
	}
}