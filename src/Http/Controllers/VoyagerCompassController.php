<?php

namespace TCG\Voyager\Http\Controllers;

use Artisan;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VoyagerCompassController extends Controller
{
    protected $request;

    public function __construct()
    {
        $this->request = app('request');
    }

    public function index(Request $request)
    {
        // Check permission
        $this->authorize('browse_compass');
        //Check if app is not local
        if (!\App::environment('local') && !config('voyager.compass_in_production', false)) {
            throw new AccessDeniedHttpException();
        }

        $message = '';
        $active_tab = '';

        if ($this->request->input('log')) {
            $active_tab = 'logs';
            try {
                LogViewer::setFile($this->decodeLogPath($this->request->input('log')));
            } catch (\Exception $e) {
                abort(404);
            }
        }

        if ($this->request->input('logs')) {
            $active_tab = 'logs';
        }

        if ($this->request->input('download')) {
            $active_tab = 'logs';

            return $this->download($this->logPath($this->request->input('download')));
        } elseif ($this->request->has('del')) {
            if (!$this->request->isMethod('post')) {
                abort(405);
            }

            $active_tab = 'logs';
            $logPath = $this->logPath($this->request->input('del'));
            app('files')->delete($logPath);

            return redirect($this->request->url().'?logs=true')->with([
                'message'    => __('voyager::compass.logs.delete_success').' '.basename($logPath),
                'alert-type' => 'success',
            ]);
        } elseif ($this->request->has('delall')) {
            if (!$this->request->isMethod('post')) {
                abort(405);
            }

            $active_tab = 'logs';
            foreach (LogViewer::getFiles(true) as $file) {
                app('files')->delete(LogViewer::pathToLogFile($file));
            }

            return redirect($this->request->url().'?logs=true')->with([
                'message'    => __('voyager::compass.logs.delete_all_success'),
                'alert-type' => 'success',
            ]);
        }

        $artisan_output = '';

        if ($request->isMethod('post') && $request->filled('command')) {
            $command = trim((string) $request->input('command'));

            try {
                if (!preg_match('/^[A-Za-z0-9:_-]+$/', $command)) {
                    throw new \InvalidArgumentException('Invalid command name.');
                }

                if (!$this->isCommandAllowed($command)) {
                    throw new \InvalidArgumentException('This command is not allowed.');
                }

                Artisan::call($command, $this->parseArtisanArguments((string) $request->input('args', '')));
                $artisan_output = Artisan::output();
            } catch (Exception $e) {
                $artisan_output = $e->getMessage();
            }
            $active_tab = 'commands';
        }

        $logs = LogViewer::all();
        $files = LogViewer::getFiles(true);
        $current_file = LogViewer::getFileName();

        // get the full list of artisan commands and store the output
        $commands = $this->getArtisanCommands();

        return view('voyager::compass.index', compact('logs', 'files', 'current_file', 'active_tab', 'commands', 'artisan_output'))->with($message);
    }

    private function logPath($encoded): string
    {
        $decoded = base64_decode((string) $encoded, true);

        try {
            return LogViewer::pathToLogFile($decoded);
        } catch (\Exception $e) {
            abort(404);
        }
    }

    private function decodeLogPath($encoded): string
    {
        $decoded = base64_decode((string) $encoded, true);

        if ($decoded === false) {
            abort(404);
        }

        return $decoded;
    }

    private function allowedCommands(): array
    {
        $allowed = config('voyager.compass.allowed_commands', [
            'list',
            'about',
            'route:list',
            'migrate:status',
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
            'optimize:clear',
        ]);

        return is_array($allowed) ? array_values(array_map('strval', $allowed)) : [];
    }

    private function isCommandAllowed(string $command): bool
    {
        $allowed = $this->allowedCommands();

        return in_array('*', $allowed, true) || in_array($command, $allowed, true);
    }

    private function parseArtisanArguments(string $args): array
    {
        if (trim($args) === '') {
            return [];
        }

        preg_match_all('/"([^"]*)"|\'([^\']*)\'|(\S+)/', $args, $matches, PREG_SET_ORDER);

        $parameters = [];

        foreach ($matches as $match) {
            $token = $match[1] !== '' ? stripcslashes($match[1]) : ($match[2] !== '' ? stripcslashes($match[2]) : $match[3]);

            if ($token === '') {
                continue;
            }

            if (str_starts_with($token, '--')) {
                $option = substr($token, 2);

                if ($option === '') {
                    continue;
                }

                if (str_contains($option, '=')) {
                    [$key, $value] = explode('=', $option, 2);
                    $parameters['--'.$key] = $value;
                } else {
                    $parameters['--'.$option] = true;
                }

                continue;
            }

            $parameters[] = $token;
        }

        return $parameters;
    }

    private function getArtisanCommands()
    {
        Artisan::call('list');

        // Get the output from the previous command
        $artisan_output = Artisan::output();
        $artisan_output = $this->cleanArtisanOutput($artisan_output);
        $commands = $this->getCommandsFromOutput($artisan_output);
        $allowed = $this->allowedCommands();

        if (!in_array('*', $allowed, true)) {
            $commands = array_values(array_filter($commands, function ($command) use ($allowed) {
                return in_array($command->name, $allowed, true);
            }));
        }

        return $commands;
    }

    private function cleanArtisanOutput($output)
    {

        // Add each new line to an array item and strip out any empty items
        $output = array_filter(explode("\n", $output));

        // Get the current index of: "Available commands:"
        $index = array_search('Available commands:', $output);

        // Remove all commands that precede "Available commands:", and remove that
        // Element itself -1 for offset zero and -1 for the previous index (equals -2)
        $output = array_slice($output, $index - 2, count($output));

        return $output;
    }

    private function getCommandsFromOutput($output)
    {
        $commands = [];

        foreach ($output as $output_line) {
            if (empty(trim(substr($output_line, 0, 2)))) {
                $parts = preg_split('/  +/', trim($output_line));
                $command = (object) ['name' => trim(@$parts[0]), 'description' => trim(@$parts[1])];
                array_push($commands, $command);
            }
        }

        return $commands;
    }

    private function download($data)
    {
        return response()->download($data);
    }
}

/***
**** Credit for the LogViewer class
**** https://github.com/rap2hpoutre/laravel-log-viewer
***/

class LogViewer
{
    /**
     * @var string file
     */
    private static $file;

    private static $levels_classes = [
        'debug'     => 'info',
        'info'      => 'info',
        'notice'    => 'info',
        'warning'   => 'warning',
        'error'     => 'danger',
        'critical'  => 'danger',
        'alert'     => 'danger',
        'emergency' => 'danger',
        'processed' => 'info',
    ];

    private static $levels_imgs = [
        'debug'     => 'info',
        'info'      => 'info',
        'notice'    => 'info',
        'warning'   => 'warning',
        'error'     => 'warning',
        'critical'  => 'warning',
        'alert'     => 'warning',
        'emergency' => 'warning',
        'processed' => 'info',
    ];

    /**
     * Log levels that are used.
     *
     * @var array
     */
    private static $log_levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
        'processed',
    ];

    public const MAX_FILE_SIZE = 52428800; // Why? Uh... Sorry

    /**
     * @param string $file
     */
    public static function setFile($file)
    {
        $file = self::pathToLogFile($file);

        if (app('files')->exists($file)) {
            self::$file = $file;
        }
    }

    /**
     * @param string $file
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function pathToLogFile($file)
    {
        if (!is_string($file) || $file === '' || str_contains($file, "\0")) {
            throw new \Exception('No such log file');
        }

        $logsPath = realpath(storage_path('logs'));

        if ($logsPath === false || !is_dir($logsPath)) {
            throw new \Exception('No such log file');
        }

        $candidate = self::isAbsolutePath($file)
            ? $file
            : $logsPath.DIRECTORY_SEPARATOR.$file;

        $realPath = realpath($candidate);

        if ($realPath === false || !is_file($realPath)) {
            throw new \Exception('No such log file');
        }

        $logPrefix = rtrim($logsPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (!str_starts_with($realPath, $logPrefix)) {
            throw new \Exception('No such log file');
        }

        return $realPath;
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    /**
     * @return string
     */
    public static function getFileName()
    {
        return basename(self::$file);
    }

    /**
     * @return array
     */
    public static function all()
    {
        $log = [];

        $pattern = '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*/';

        if (!self::$file) {
            $log_file = self::getFiles();
            if (!count($log_file)) {
                return [];
            }
            self::$file = $log_file[0];
        }

        if (app('files')->size(self::$file) > self::MAX_FILE_SIZE) {
            return;
        }

        $file = app('files')->get(self::$file);

        preg_match_all($pattern, $file, $headings);

        if (!is_array($headings)) {
            return $log;
        }

        $log_data = preg_split($pattern, $file);

        if ($log_data[0] < 1) {
            array_shift($log_data);
        }

        foreach ($headings as $h) {
            for ($i = 0, $j = count($h); $i < $j; $i++) {
                foreach (self::$log_levels as $level) {
                    if (strpos(strtolower($h[$i]), '.'.$level) || strpos(strtolower($h[$i]), $level.':')) {
                        preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\](?:.*?(\w+)\.|.*?)'.$level.': (.*?)( in .*?:[0-9]+)?$/i', $h[$i], $current);
                        if (!isset($current[3])) {
                            continue;
                        }

                        $log[] = [
                            'context'     => $current[2],
                            'level'       => $level,
                            'level_class' => self::$levels_classes[$level],
                            'level_img'   => self::$levels_imgs[$level],
                            'date'        => $current[1],
                            'text'        => $current[3],
                            'in_file'     => $current[4] ?? null,
                            'stack'       => preg_replace("/^\n*/", '', $log_data[$i]),
                        ];
                    }
                }
            }
        }

        return array_reverse($log);
    }

    /**
     * @param bool $basename
     *
     * @return array
     */
    public static function getFiles($basename = false)
    {
        $files = glob(storage_path().'/logs/*.log');
        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');
        if ($basename && is_array($files)) {
            foreach ($files as $k => $file) {
                $files[$k] = basename($file);
            }
        }

        return array_values($files);
    }
}
