<?php declare(strict_types=1);

/*
 * This file is part of the logtail/monolog-logtail package.
 *
 * (c) Better Stack
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Logtail\Monolog;

use Monolog\Formatter\FormatterInterface;

/**
 * Sends log to Logtail.
 */
class LogtailHandler extends \Monolog\Handler\AbstractProcessingHandler {
    /**
     * @var LogtailClient $client
     */
    private $client;

    private $channels;

    /**
     * @param string $sourceToken
     * @param string $hostname
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(
        $sourceToken,
        $level = \Monolog\Logger::DEBUG,
        $channels = [],
        $bubble = true,
        $endpoint = LogtailClient::URL
    ) {
        parent::__construct($level, $bubble);

        $this->client = new LogtailClient($sourceToken, $endpoint);

        $this->pushProcessor(new \Monolog\Processor\IntrospectionProcessor($level, ['Logtail\\']));
        $this->pushProcessor(new \Monolog\Processor\WebProcessor);
        $this->pushProcessor(new \Monolog\Processor\ProcessIdProcessor);
        $this->pushProcessor(new \Monolog\Processor\HostnameProcessor);

        $this->channels = $channels;
    }

    /**
     * @param array $record
     */
    protected function write(array $record): void {
        $skip = false;
        if (count($this->channels) > 0) {
            $excluded = array_filter($this->channels, function ($channel) {
                return substr($channel, 0, 1) === "!";
            });
            $included = array_filter($this->channels, function ($channel) {
                return substr($channel, 0, 1) !== "!";
            });
            if (count($included) > 0 && !in_array($record["channel"], $included)) $skip = true;
            if (in_array("!".$record["channel"], $excluded)) $skip = true;
        }
        if (!$skip) $this->client->send($record["formatted"]);
    }

    /**
     * @return \Logtail\Monolog\LogtailFormatter
     */
    protected function getDefaultFormatter(): \Monolog\Formatter\FormatterInterface {
        return new \Logtail\Monolog\LogtailFormatter();
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->getDefaultFormatter();
    }
}
