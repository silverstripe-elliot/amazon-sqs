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
	 * @return AmazonSQS
	 */
	public function findOrMakeQueue($queueName) {
		$sqs = $this->sqs->createQueue(array('QueueName' => $queueName));
		$this->queueUrl = $sqs->get('QueueUrl');
		return $this;
	}

	/**
	 * Send a message to the current Amazon queue
	 *
	 * Known issues:
	 * - is this getting sent more than once?
	 * @param  mixed $data
	 * @return AmazonSQS
	 */
	public function enqueue($data) {
		$serializedData = serialize($data);
		$this->sqs->sendMessage(array(
			'QueueUrl' => $this->queueUrl,
			'MessageBody' => $serializedData
		));

		return $this;
	}

	/**
	 * Receive a message from the current Amazon queue
	 * 
	 * Known issue:
	 * - for some reason Amazon does not return the correct message, or it is not in the correct order. 
	 * @todo: dequeue more than one item
	 * @return array of Message, or null if there is no message
	 */
	public function dequeue() {
		$messages = $this->sqs->receiveMessage(array(
			'QueueUrl' => $this->queueUrl
		));
		if($messages) {
			$message = $messages->getPath('Messages/0');
			$message['Body'] = unserialize($message['Body']);
			return $message;
		}
		return null;
	}

	/**
	 * Delete message - indicate to Amazon we received the message, by returning part of it
	 * @param  [type] $receiptHandle receipt handle field of returned message
	 * @return AmazonSQS
	 */
	public function deleteMessage($receiptHandle) {
		$this->sqs->deleteMessage(array(
			'QueueUrl' => $this->queueUrl,
			'ReceiptHandle' => $receiptHandle
		));

		return $this;
	}
}
