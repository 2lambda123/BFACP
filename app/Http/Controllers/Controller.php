<?php

namespace BFACP\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

/**
 * Class Controller.
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Stores the currently logged in user.
     *
     * @var \BFACP\Account\User
     */
    protected $user;

    /**
     * Is the user logged in?
     *
     * @var bool
     */
    protected $isLoggedIn = false;

    /**
     * Any messages to be pushed to the view.
     *
     * @var array
     */
    public $messages = [];

    /**
     * Any error messages to be pushed to the view.
     *
     * @var array
     */
    public $errors = [];

    /**
     *
     */
    public function __construct()
    {
        $this->user = Auth::user();
        $this->isLoggedIn = Auth::check();
    }
}