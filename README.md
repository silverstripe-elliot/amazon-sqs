# amazon-sqs
Silverstripe module for sending and receiving data from the Amazon Simple Queueing Service

# Installation
1. Clone repository into your Silverstripe webroot. 
2. Add your Amazon credentials into SS Environment file
3. Run dev/build flush=all

# Usage
##_ss_environment.php
```php

	define('AWS_ACCESS_KEY', 'xxxxxxx');
	define('AWS_ACCESS_SECRET', 'xxxxxxx');
	define('AWS_REGION_NAME', 'ap-xxxxx-x');
```

##Send a message to SQS
```php
	$queue = AmazonSQS::create()
		->findOrMakeQueue('mytestqueue')
		->enqueue($jobData);
```

##Receive a message from SQS
```php
	$queue = AmazonSQS::create();
	$jobData = $queue->findOrMakeQueue('mytestqueue')
		->dequeue();

```
Message is in $jobData['Body']. You need to save $jobData['ReceiptHandle'] from this, to delete the message later

##Delete a message that you have received from SQS queue
You need to indicate to SQS that you've received the message before it is removed from the Queue. To do this, you must send back the ReceiptHandle that was received with the message
```php
	$queue = AmazonSQS::create();

	if($this->messageReceiptHandle) {
		$deleted = $queue->findOrMakeQueue('mytestqueue')
			->deleteMessage($this->messageReceiptHandle);
	}
```

#Known issues
- works really inconsistently, but unsure if it's the API or how I am using it.
- receiving messages is wonky. Sometimes received out of order, or multiple times.
- now sure how (or if) we need to delete the message after receiving it

