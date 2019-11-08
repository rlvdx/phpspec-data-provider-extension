<?php

namespace Kanel\PhpSpec\DataProvider;

use PhpSpec\Loader\Node\ExampleNode;
use PhpSpec\Runner\CollaboratorManager;
use PhpSpec\Runner\MatcherManager;
use PhpSpec\Specification;

class Maintainer implements \PhpSpec\Runner\Maintainer\Maintainer
{
	protected $providedData;
	const EXAMPLE_NUMBER_PATTERN = '/^(\d+)\)/';

	public function supports(ExampleNode $example): bool
	{
		return $this->haveValidDataProvider($example);
	}

	public function prepare(ExampleNode $example, Specification $context, MatcherManager $matchers, CollaboratorManager $collaborators): void
	{
		$exampleNum = $this->getExampleNumber($example->getTitle());
		if (! array_key_exists($exampleNum, $this->providedData)) {
			return ;
		}

		$data = $this->providedData[$exampleNum];
		foreach ($example->getFunctionReflection()->getParameters() as $position => $parameter) {
			if (isset($data[$position])) {
				$collaborators->set($parameter->getName(), $data[$position]);
			} elseif ($parameter->isOptional()) {
				$collaborators->set($parameter->getName(), $parameter->getDefaultValue());
			}
		}
	}

	public function teardown(ExampleNode $example, Specification $context, MatcherManager $matchers, CollaboratorManager $collaborators): void
	{
	}

	public function getPriority(): int
	{
		return 50;
	}

	private function haveValidDataProvider(ExampleNode $example)
	{
		$parser = new Parser();
		$dataProviderMethod = $parser->getDataProvider($example->getFunctionReflection());
		if (!isset($dataProviderMethod)) {
			return false;
		}

		if (!$example->getSpecification()->getClassReflection()->hasMethod($dataProviderMethod)) {
			return false;
		}

		$subject = $example->getSpecification()->getClassReflection()->newInstance();
		$providedData = $example->getSpecification()->getClassReflection()->getMethod($dataProviderMethod)->invoke($subject);
		if (!is_array($providedData)) {
			return false;
		}

		foreach ($providedData as $dataRow) {
			if (!is_array($dataRow)) {
				return false;
			}
		}

		$this->providedData = $providedData;

		return true;
	}

	private function getExampleNumber($title)
	{
		if (0 === preg_match(self::EXAMPLE_NUMBER_PATTERN, $title, $matches)) {
			return 0;
		}
		return (int) $matches[1] - 1;
	}
}