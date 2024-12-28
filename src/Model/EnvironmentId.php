<?php

namespace Drall\Model;

enum EnvironmentId: string {

  case Development = 'development';

  case Test = 'test';

  case Unknown = 'unknown';

  /**
   * Whether the environment is currently active.
   *
   * For example, progress bars are automatically hidden for the "test"
   * environment to prevent them from polluting the output.
   *
   * @return bool
   *   True or False.
   */
  public function isActive(): bool {
    return getenv('DRALL_ENVIRONMENT') === $this->value;
  }

}
