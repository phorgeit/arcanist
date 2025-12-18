<?php

/**
 * @phutil-external-symbol class PhpParser\Token
 */
final class ArcanistTestPHPASTLintSwitchHook
  extends ArcanistPHPASTLintSwitchHook {

  public function checkSwitchToken(PhpParser\Token $token) {
    if ($token->is(T_STRING)) {
      switch (strtolower($token->text)) {
        case 'throw_exception':
          return true;
      }
    }
    return false;
  }

}
