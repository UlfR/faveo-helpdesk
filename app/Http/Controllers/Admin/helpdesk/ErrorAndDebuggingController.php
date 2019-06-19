<?php

namespace App\Http\Controllers\Admin\helpdesk;

// controller
use App\Http\Controllers\Controller;
// request

use Exception;
use File;
use Lang;

/**
 * ErrorAndDebuggingController.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class ErrorAndDebuggingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->smtp();
        $this->middleware('auth');
        $this->middleware('roles');
    }

    /**
     * function to show error and debugging setting page.
     *
     * @param void
     *
     * @return response
     */
    public function showSettings()
    {
        $debug = \Config::get('app.debug');

        return view('themes.default1.admin.helpdesk.settings.error-and-logs.error-debug')->with(['debug' => $debug]);
    }

    /**
     * funtion to update error and debugging settings.
     *
     * @param void
     *
     * @return
     */
    public function postSettings()
    {
        try {
            $debug = \Config::get('app.debug');
            $debug = ($debug) ? 'true' : 'false';
            if ($debug != \Input::get('debug')) {
                // dd($request->input());
                $debug_new = base_path().DIRECTORY_SEPARATOR.'.env';
                $datacontent = File::get($debug_new);
                $datacontent = str_replace('APP_DEBUG='.$debug,
                                           'APP_DEBUG='.\Input::get('debug'),
                                            $datacontent);
                File::put($debug_new, $datacontent);

                // dd($request->input());
                return redirect()->back()->with('success',
                    Lang::get('lang.error-debug-settings-saved-message'));
            } else {
                return redirect()->back()->with('fails',
                    Lang::get('lang.error-debug-settings-error-message'));
            }
        } catch (Exception $e) {
            /* redirect to Index page with Fails Message */
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * function to show error log table page.
     *
     * @param void
     *
     * @return response view
     */
    public function showErrorLogs()
    {
        return view('themes.default1.admin.helpdesk.settings.error-and-logs.log-table');
    }
}
