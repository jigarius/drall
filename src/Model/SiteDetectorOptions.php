<?php

namespace Drall\Model;

use Symfony\Component\Console\Input\InputInterface;

class SiteDetectorOptions {

  private ?string $filter = NULL;

  private ?string $group = NULL;

  private ?int $limit = NULL;

  private ?int $offset = NULL;

  public function __construct() {}

  public static function fromArray(array $options): static {
    $result = new SiteDetectorOptions();

    if ($group = $options['group'] ?? getenv('DRALL_GROUP')) {
      $result->setGroup($group);
    }

    if (isset($options['filter'])) {
      $result->setFilter($options['filter']);
    }

    if (isset($options['offset'])) {
      if (!is_numeric($options['offset'])) {
        throw new \InvalidArgumentException('Offset must be an integer.');
      }

      $result->setOffset($options['offset']);
    }

    if (isset($options['limit'])) {
      if (!is_numeric($options['limit'])) {
        throw new \InvalidArgumentException('Limit must be an integer.');
      }

      $result->setLimit($options['limit']);
    }

    return $result;
  }

  public static function fromInput(InputInterface $input): static {
    $aOptions = [];

    if (
      $input->hasOption('group') &&
      $group = $input->getOption('group')
    ) {
      $aOptions['group'] = $group;
    }

    if (
      $input->hasOption('filter') &&
      $filter = $input->getOption('filter')
    ) {
      $aOptions['filter'] = $filter;
    }

    if (
      $input->hasOption('offset') &&
      $offset = $input->getOption('offset')
    ) {
      $aOptions['offset'] = $offset;
    }

    if (
      $input->hasOption('limit') &&
      $limit = $input->getOption('limit')
    ) {
      $aOptions['limit'] = $limit;
    }

    return self::fromArray($aOptions);
  }

  public function getFilter(): ?string {
    return $this->filter;
  }

  public function setFilter(?string $filter): static {
    $this->filter = $filter;
    return $this;
  }

  public function getGroup(): ?string {
    return $this->group;
  }

  public function setGroup(?string $group): static {
    $this->group = $group;
    return $this;
  }

  public function getOffset(): ?int {
    return $this->offset;
  }

  public function setOffset(?int $offset): static {
    $this->offset = $offset;
    return $this;
  }

  public function getLimit(): ?int {
    return $this->limit;
  }

  public function setLimit(?int $limit): static {
    if ($limit < 1) {
      throw new \ValueError('Limit must be greater than zero.');
    }

    $this->limit = $limit;
    return $this;
  }

}
