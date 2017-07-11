<?php namespace GO;

use DateTime;
use Exception;
use GO\Traits\TraitsAlternative;
use InvalidArgumentException;

class Job extends TraitsAlternative
{
    /**
     * Job identifier.
     *
     * @var string
     */
    protected $id;

    /**
     * Command to execute.
     *
     * @var mixed
     */
    protected $command;

    /**
     * Arguments to be passed to the command.
     *
     * @var array
     */
    protected $args = array();

    /**
     * Defines if the job should run in background.
     *
     * @var bool
     */
    protected $runInBackground = true;

    /**
     * Creation time.
     *
     * @var DateTime
     */
    protected $creationTime;

    /**
     * Job schedule time.
     *
     * @var CronExpression
     */
    protected $executionTime;

    /**
     * Temporary directory path for
     * lock files to prevent overlapping.
     *
     * @var string
     */
    protected $tempDir;

    /**
     * Path to the lock file.
     *
     * @var string
     */
    protected $lockFile;

    /**
     * This could prevent the job to run.
     * If true, the job will run (if due).
     *
     * @var bool
     */
    protected $truthTest = true;

    /**
     * The output of the executed job.
     *
     * @var mixex
     */
    protected $output;

    /**
     * Files to write the output of the job.
     *
     * @var array
     */
    protected $outputTo = array();

    /**
     * Email addresses where the output should be sent to.
     *
     * @var array
     */
    protected $emailTo = array();

    /**
     * Configuration for email sending.
     *
     * @var array
     */
    protected $emailConfig = array();

    /**
     * A function to execute after the job is executed.
     *
     * @var callable
     */
    protected $after;

    /**
     * A function to ignore an overlapping job.
     * If true, the job will run also if it's overlapping.
     *
     * @var callable
     */
    protected $whenOverlapping;

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * Create a new Job instance.
     *
     * @param  string\callable $command
     * @param  array           $args
     * @param  string          $id
     */
    public function __construct($command, $args = array(), $id = null)
    {
        if (is_string($id)) {
            $this->id = $id;
        } else {
            if (is_string($command)) {
                $this->id = md5($command);
            } else {
                $this->id = spl_object_hash($command);
            }
        }

        $this->creationTime = new DateTime('now');

        // initialize the directory path for lock files
        $this->tempDir = sys_get_temp_dir();

        $this->command = $command;
        $this->args = $args;
    }

    /**
     * Get the Job id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Check if the Job is due to run.
     * It accepts as input a DateTime used to check if
     * the job is due. Defaults to job creation time.
     * It also default the execution time if not previously defined.
     *
     * @param  DateTime  $date
     * @return bool
     */
    public function isDue(DateTime $date = null)
    {
        // The execution time is being defaulted if not defined
        if (! $this->executionTime) {
            $this->at('* * * * *');
        }

        $date = $date !== null ? $date : $this->creationTime;

        return $this->executionTime->isDue($date);
    }

    /**
     * Check if the Job is overlapping.
     *
     * @return bool
     */
    public function isOverlapping()
    {
        return $this->lockFile &&
               file_exists($this->lockFile) &&
               call_user_func($this->whenOverlapping, filemtime($this->lockFile)) === false;
    }

    /**
     * Force the Job to run in foreground.
     *
     * @return this
     */
    public function inForeground()
    {
        $this->runInBackground = false;

        return $this;
    }

    /**
     * Check if the Job can run in background.
     *
     * @return bool
     */
    public function canRunInBackground()
    {
        if (is_callable($this->command) || $this->runInBackground === false) {
            return false;
        }

        return true;
    }

    /**
     * This will prevent the Job from overlapping.
     * It prevents another instance of the same Job of
     * being executed if the previous is still running.
     * The job id is used as a filename for the lock file.
     *
     * @param  string    $tempDir          The directory path for the lock files
     * @param  callable  $whenOverlapping  A callback to ignore job overlapping
     * @return this
     */
    public function onlyOne($tempDir = null, callable $whenOverlapping = null)
    {
        if ($tempDir === null || ! is_dir($tempDir)) {
            $tempDir = $this->tempDir;
        }

        $this->lockFile = implode('/', array(
            trim($tempDir),
            trim($this->id) . '.lock',
        ));

        if ($whenOverlapping) {
            $this->whenOverlapping = $whenOverlapping;
        } else {
            $this->whenOverlapping = function () {
                return false;
            };
        }

        return $this;
    }

    /**
     * Compile the Job command.
     *
     * @return mixed
     */
    public function compile()
    {
        $compiled = $this->command;

        // If callable, return the function itself
        if (is_callable($compiled)) {
            return $compiled;
        }

        // Augment with any supplied arguments
        foreach ($this->args as $key => $value) {
            $compiled .= ' ' . escapeshellarg($key);
            if ($value !== null) {
                $compiled .= ' ' . escapeshellarg($value);
            }
        }

        // Add the boilerplate to redirect the output to file/s
        if (count($this->outputTo) > 0) {
            $compiled .= ' | tee ';
            $compiled .= $this->outputMode === 'a' ? '-a ' : '';
            foreach ($this->outputTo as $file) {
                $compiled .= $file . ' ';
            }

            $compiled = trim($compiled);
        }

        // Add boilerplate to remove lockfile after execution
        if ($this->lockFile) {
            $compiled .= '; rm ' . $this->lockFile;
        }

        // Add boilerplate to run in background
        if ($this->canRunInBackground()) {
            // Parentheses are need execute the chain of commands in a subshell
            // that can then run in background
            $compiled = '(' . $compiled . ') > /dev/null 2>&1 &';
        }

        return trim($compiled);
    }

    /**
     * Configure the job.
     *
     * @param  array  $config
     * @return this
     */
    public function configure(array $config = array())
    {
        if (isset($config['email'])) {
            if (! is_array($config['email'])) {
                throw new InvalidArgumentException('Email configuration should be an array.');
            }
            $this->emailConfig = $config['email'];
        }

        // Check if config has defined a tempDir
        if (isset($config['tempDir']) && is_dir($config['tempDir'])) {
            $this->tempDir = $config['tempDir'];
        }

        return $this;
    }

    /**
     * Truth test to define if the job should run if due.
     *
     * @param  callable  $fn
     * @return this
     */
    public function when(callable $fn)
    {
        $this->truthTest = $fn();

        return $this;
    }

    /**
     * Run the job.
     *
     * @return bool
     */
    public function run()
    {
        // If the truthTest failed, don't run
        if ($this->truthTest !== true) {
            return false;
        }

        // If overlapping, don't run
        if ($this->isOverlapping()) {
            return false;
        }

        $compiled = $this->compile();

        // Write lock file if necessary
        $this->createLockFile();

        if (is_callable($compiled)) {
            $this->output = $this->exec($compiled);
        } else {
            $this->output = exec($compiled);
        }

        $this->finalise();

        return true;
    }

    /**
     * Create the job lock file.
     *
     * @param  mixed  $content
     * @return void
     */
    private function createLockFile($content = null)
    {
        if ($this->lockFile) {
            if ($content === null || ! is_string($content)) {
                $content = $this->getId();
            }

            file_put_contents($this->lockFile, $content);
        }
    }

    /**
     * Remove the job lock file.
     *
     * @return void
     */
    private function removeLockFile()
    {
        if ($this->lockFile && file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }

    /**
     * Execute a callable job.
     *
     * @param  callable $fn
     * @return string
     * @throws Exception
     */
    private function exec(callable $fn)
    {
        ob_start();

        try {
            $returnData = call_user_func_array($fn, $this->args);
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }

        $outputBuffer = ob_get_clean();

        foreach ($this->outputTo as $filename) {
            if ($outputBuffer) {
                file_put_contents($filename, $outputBuffer, $this->outputMode === 'a' ? FILE_APPEND : 0);
            }

            if ($returnData) {
                file_put_contents($filename, $returnData, FILE_APPEND);
            }
        }

        $this->removeLockFile();

        return $outputBuffer . (is_string($returnData) ? $returnData : '');
    }

    /**
     * Set the file/s where to write the output of the job.
     *
     * @param  string\array  $filename
     * @param  bool          $append
     * @return this
     */
    public function output($filename, $append = false)
    {
        $this->outputTo = is_array($filename) ? $filename : array($filename);
        $this->outputMode = $append === false ? 'w' : 'a';

        return $this;
    }

    /**
     * Get the job output.
     *
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Set the emails where the output should be sent to.
     * The Job should be set to write output to a file
     * for this to work.
     *
     * @param  string\array  $email
     * @return this
     */
    public function email($email)
    {
        if (! is_string($email) && ! is_array($email)) {
            throw new InvalidArgumentException('The email can be only string or array');
        }

        $this->emailTo = is_array($email) ? $email : array($email);

        // Force the job to run in foreground
        $this->inForeground();

        return $this;
    }

    /**
     * Finilise the job after execution.
     *
     * @return void
     */
    private function finalise()
    {
        // Send output to email
        $this->emailOutput();

        // Call any callback defined
        if (is_callable($this->after)) {
            call_user_func($this->after, $this->output);
        }
    }

    /**
     * Email the output of the job, if any.
     *
     * @return void
     */
    private function emailOutput()
    {
        if (! count($this->outputTo) || ! count($this->emailTo)) {
            return false;
        }

        $this->sendToEmails($this->outputTo);
    }

    /**
     * Set a function to be called after job execution.
     * By default this will force the job to run in foreground
     * because the output is injected as a parameter of this
     * function, but it could be avoided by passing true as a
     * second parameter. The job will run in background if it
     * meets all the other criteria.
     *
     * @param  callable  $fn
     * @param  bool      $runInBackground
     * @return this
     */
    public function then(callable $fn, $runInBackground = false)
    {
        $this->after = $fn;

        // Force the job to run in foreground
        if ($runInBackground === false) {
            $this->inForeground();
        }

        return $this;
    }
}
