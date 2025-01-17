<?php
namespace Clicalmani\Foundation\Database;

use Clicalmani\Foundation\Support\Facades\Facade;

/**
 * Class Model
 * 
 * This is the base model class that extends the Facade class.
 * It provides common functionality for all models in the application.
 * 
 * @package Clicalmani\Foundation
 * 
 * @method static bool create(array $fields = [], ?bool $replace = false)
 * @method static bool createOrFail(array $fields = [], ?bool $replace = false)
 */
class Model extends Facade
{}