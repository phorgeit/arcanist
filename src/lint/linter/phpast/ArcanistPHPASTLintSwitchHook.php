<?php

/**
 * You can extend this class and set `phpast.switchhook` in your `.arclint`
 * to have an opportunity to override results for linting `switch` statements.
 *
 * @phutil-external-symbol class PhpParser\Token
 */
abstract class ArcanistPHPASTLintSwitchHook extends Phobject {

  /**
   * @return bool True if token safely ends the block.
   */
  abstract public function checkSwitchToken(PhpParser\Token $token);

}
