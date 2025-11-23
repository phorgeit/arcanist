<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 * @phutil-external-symbol class PhpParser\Node\Stmt\Trait_
 */
final class ArcanistClassNameLiteralPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 62;

  public function getLintName() {
    return pht('Class Name Literal');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Stmt\ClassLike && $node->name) {
      $strings = PhpParserAst::newPartialAst($node->getMethods())
        ->findNodesOfKind(
          PhpParser\Node\Scalar\String_::class,
          array(
            PhpParser\Node\Stmt\ClassLike::class,
          ));

      foreach ($strings as $string) {
        $replacement = null;

        if ($string->value === $node->name->toString()) {
          if ($node instanceof PhpParser\Node\Stmt\Trait_) {
            $replacement = '__TRAIT__';
          } else {
            $replacement = '__CLASS__';
          }
        }

        // NOTE: We're only warning when the entire string content is the
        // class name. It's okay to hard-code the class name as part of a
        // longer string, like an error or exception message.

        // Sometimes the class name (like "Filesystem") is also a valid part
        // of the message, which makes this warning a false positive.

        // Even when we're generating a true positive by detecting a class
        // name in part of a longer string, the cost of an error message
        // being out-of-date is generally very small (mild confusion, but
        // no incorrect beahavior) and using "__CLASS__" in errors is often
        // clunky.

        $regex = '(^'.preg_quote($node->name->toString()).'$)';
        if (!preg_match($regex, $string->value)) {
          continue;
        }

        $this->raiseLintAtNode(
          $string,
          pht(
            'Prefer "__CLASS__" or "__TRAIT__" over hard-coded class '.
            'or trait names.'),
          $replacement,
          $token_stream);
      }
    }
  }

}
