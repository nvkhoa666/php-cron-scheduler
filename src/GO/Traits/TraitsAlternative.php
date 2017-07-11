<?php
namespace GO\Traits;

use Cron\CronExpression;
use InvalidArgumentException;


class TraitsAlternative
{
    /**
     * Set the execution time to every minute.
     *
     * @return this
     */
    public function everyMinute()
    {
        return $this->at('* * * * *');
    }

    /**
     * Set the Job execution time.
     *
     * @param  string $expression
     * @return this
     */
    public function at($expression)
    {
        $this->executionTime = CronExpression::factory($expression);

        return $this;
    }

    /**
     * Set the execution time to every hour.
     *
     * @param  int\string $minute
     * @return this
     */
    public function hourly($minute = 0)
    {
        $c = $this->validateCronSequence($minute);

        return $this->at("{$c['minute']} * * * *");
    }

    /**
     * Validate sequence of cron expression.
     *
     * @param  int\string $minute
     * @param  int\string $hour
     * @param  int\string $day
     * @param  int\string $month
     * @param  int\string $weekday
     * @return array
     */
    protected function validateCronSequence($minute = null, $hour = null, $day = null, $month = null, $weekday = null)
    {
        return array(
            'minute'  => $this->validateCronRange($minute, 0, 59),
            'hour'    => $this->validateCronRange($hour, 0, 23),
            'day'     => $this->validateCronRange($day, 1, 31),
            'month'   => $this->validateCronRange($month, 1, 12),
            'weekday' => $this->validateCronRange($weekday, 0, 6),
        );
    }

    /**
     * Validate sequence of cron expression.
     *
     * @param  int\string $value
     * @param  int        $min
     * @param  int        $max
     * @return mixed
     */
    protected function validateCronRange($value, $min, $max)
    {
        if ($value === null || $value === '*') {
            return '*';
        }

        if (!is_numeric($value) ||
            !($value >= $min && $value <= $max)
        ) {
            throw new InvalidArgumentException(
                "Invalid value: it should be '*' or between {$min} and {$max}."
            );
        }

        return $value;
    }

    /**
     * Set the execution time to once a day.
     *
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function daily($hour = 0, $minute = 0)
    {
        if (is_string($hour)) {
            $parts  = explode(':', $hour);
            $hour   = $parts[0];
            $minute = isset($parts[1]) ? $parts[1] : '0';
        }

        $c = $this->validateCronSequence($minute, $hour);

        return $this->at("{$c['minute']} {$c['hour']} * * *");
    }

    /**
     * Set the execution time to every Sunday.
     *
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function sunday($hour = 0, $minute = 0)
    {
        return $this->weekly(0, $hour, $minute);
    }

    /**
     * Set the execution time to once a week.
     *
     * @param  int\string $weekday
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function weekly($weekday = 0, $hour = 0, $minute = 0)
    {
        if (is_string($hour)) {
            $parts  = explode(':', $hour);
            $hour   = $parts[0];
            $minute = isset($parts[1]) ? $parts[1] : '0';
        }

        $c = $this->validateCronSequence($minute, $hour, null, null, $weekday);

        return $this->at("{$c['minute']} {$c['hour']} * * {$c['weekday']}");
    }

    /**
     * Set the execution time to every Monday.
     *
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function monday($hour = 0, $minute = 0)
    {
        return $this->weekly(1, $hour, $minute);
    }

    /**
     * Set the execution time to every Tuesday.
     *
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function tuesday($hour = 0, $minute = 0)
    {
        return $this->weekly(2, $hour, $minute);
    }

    /**
     * Set the execution time to every Wednesday.
     *
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function wednesday($hour = 0, $minute = 0)
    {
        return $this->weekly(3, $hour, $minute);
    }

    /**
     * Set the execution time to every Thursday.
     *
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function thursday($hour = 0, $minute = 0)
    {
        return $this->weekly(4, $hour, $minute);
    }

    /**
     * Set the execution time to every Friday.
     *
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function friday($hour = 0, $minute = 0)
    {
        return $this->weekly(5, $hour, $minute);
    }

    /**
     * Set the execution time to every Saturday.
     *
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function saturday($hour = 0, $minute = 0)
    {
        return $this->weekly(6, $hour, $minute);
    }

    /**
     * Set the execution time to every January.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function january($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(1, $day, $hour, $minute);
    }

    /**
     * Set the execution time to once a month.
     *
     * @param  int\string $month
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function monthly($month = '*', $day = 1, $hour = 0, $minute = 0)
    {
        if (is_string($hour)) {
            $parts  = explode(':', $hour);
            $hour   = $parts[0];
            $minute = isset($parts[1]) ? $parts[1] : '0';
        }

        $c = $this->validateCronSequence($minute, $hour, $day, $month);

        return $this->at("{$c['minute']} {$c['hour']} {$c['day']} {$c['month']} *");
    }

    /**
     * Set the execution time to every February.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function february($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(2, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every March.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function march($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(3, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every April.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function april($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(4, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every May.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function may($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(5, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every June.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function june($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(6, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every July.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function july($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(7, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every August.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function august($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(8, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every September.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function september($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(9, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every October.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function october($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(10, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every November.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function november($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(11, $day, $hour, $minute);
    }

    /**
     * Set the execution time to every December.
     *
     * @param  int\string $day
     * @param  int\string $hour
     * @param  int\string $minute
     * @return this
     */
    public function december($day = 1, $hour = 0, $minute = 0)
    {
        return $this->monthly(12, $day, $hour, $minute);
    }

    /**
     * Get email configuration.
     *
     * @return array
     */
    public function getEmailConfig()
    {
        $config = array();
        if (! isset($this->emailConfig['subject']) ||
            ! is_string($this->emailConfig['subject'])
        ) {
            $this->emailConfig['subject'] = 'Cronjob execution';
        }

        if (! isset($this->emailConfig['from'])) {
            $this->emailConfig['from'] = array('cronjob@server.my' => 'My Email Server');
        }

        if (! isset($this->emailConfig['body']) ||
            ! is_string($this->emailConfig['body'])
        ) {
            $this->emailConfig['body'] = 'Cronjob output attached';
        }

        if (! isset($this->emailConfig['transport']) ||
            ! ($this->emailConfig['transport'] instanceof \Swift_Transport)
        ) {
            $this->emailConfig['transport'] = new \Swift_SendmailTransport();
        }

        return $this->emailConfig;
    }

    /**
     * Send files to emails.
     *
     * @param  array  $files
     * @return void
     */
    protected function sendToEmails(array $files)
    {
        $mailer = new \Swift_Mailer($this->emailConfig['transport']);

        $config = $this->getEmailConfig();

        $swiftMessage = new \Swift_Message();
        $message = $swiftMessage
            ->setSubject($config['subject'])
            ->setFrom($config['from'])
            ->setTo($this->emailTo)
            ->setBody($config['body'])
            ->addPart('<q>Cronjob output attached</q>', 'text/html');

        foreach ($files as $filename) {
            $message->attach(\Swift_Attachment::fromPath($filename));
        }

        $mailer->send($message);
    }

}