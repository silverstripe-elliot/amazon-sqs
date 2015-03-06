<?php
require_once BASE_PATH . '/vendor/autoload.php';
use Aws\Common\Aws;

class AmazonSQS extends Object{

	public $sqs;

	public $queueName;
	public $queueUrl;

	/**
	 * Initialize the Amazon Simple Queuing service from credentials 
	 * loaded into your environment
	 *
	 * For this class to work, you need to have a set of Amazon credentials and
	 * a region defined in _ss_environment.php. Define the following constants:
	 * - AWS_ACCESS_KEY
	 * - AWS_ACCESS_SECRET
	 * - AWS_REGION_NAME 
	 * @todo  Pass credentials in through the constructor
	 * @param  string queueName name of the queue we're sending data to and from
	 * @return Aws\Sqs\SqsClient
	 */
	public function __construct() {

		if(!(defined('AWS_ACCESS_KEY') && defined('AWS_ACCESS_SECRET') && defined('AWS_REGION_NAME') ) ) {
			throw new Exception('AWS_ACCESS_KEY, AWS_ACCESS_SECRET, and AWS_REGION_NAME must be defined');
		}

		if(class_exists('Aws\Common\Aws')) {
			$this->sqs = Aws::factory(array(
				'key'    => AWS_ACCESS_KEY,
				'secret' => AWS_ACCESS_SECRET,
				'region' => AWS_REGION_NAME
			))->get('Sqs');
		} else {
			throw new Exception ('Amazon SDK is not loaded');
		}

		return $this->sqs;
	}

	/**
	 * Create a new queue, or get an existing one
	 * @param  string $queueName Name of the queue that we're going to use
	 * @return Aws\Sqs\SqsClient
	 */
	public function findOrMakeQueue($queueName) {
		$sqs = $this->sqs->createQueue(array('QueueName' => $queueName));
		$this->queueUrl = $sqs->get('QueueUrl');
		return $this->sqs;
	}

	/**
	 * Send a message to the current Amazon queue
	 * @param  mixed $data
	 * @return Guzzle\Service\Resource\Model
	 */
	public function enqueue($data) {
		$serializedData = serialize($data);
		return $this->sqs->sendMessage(array(
			'QueueUrl' => $this->queueUrl,
			'MessageBody' => $serializedData
		));
	}

	/**
	 * Receive a message from the current Amazon queue
	 * @param  integer $num number of messages to dequeue
	 * @todo: dequeue more than one item
	 * @return unserialized string, or null if there is no message
	 */
	public function dequeue() {
		$message = $this->sqs->receiveMessage(array(
			'QueueUrl' => $this->queueUrl
		));
		if($message) {
			$body = $message->getPath('Messages/*/Body');
			return count($body) ? unserialize($body[0]) : null;
		}
		return null;
	}
}
