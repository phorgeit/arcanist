<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Switch_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Break_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Continue_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\Stmt\Do_
 * @phutil-external-symbol class PhpParser\Node\Stmt\While_
 * @phutil-external-symbol class PhpParser\Node\Stmt\For_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Switch_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Return_
 * @phutil-external-symbol class PhpParser\Node\Expr\Throw_
 * @phutil-external-symbol class PhpParser\Node\Expr\Exit_
 */
final class ArcanistImplicitFallthroughPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 30;

  private $switchhook;

  public function getLintName() {
    return pht('Implicit Fallthrough');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function getLinterConfigurationOptions() {
    return parent::getLinterConfigurationOptions() + array(
      'phpast.switchhook' => array(
        'type' => 'optional string',
        'help' => pht(
          'Name of a concrete subclass of `%s` which tunes the '.
          'analysis of `%s` statements for this linter.',
          ArcanistPHPASTLintSwitchHook::class,
          'switch'),
      ),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'phpast.switchhook':
        $this->switchhook = $value;
        return;

      default:
        parent::setLinterConfigurationValue($key, $value);
        return;
    }
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    static $hook_obj = null;

    if (!$hook_obj && $this->switchhook) {
      $hook_obj = newv($this->switchhook, array());
      assert_instances_of(
        array($hook_obj),
        ArcanistPHPASTLintSwitchHook::class);
    }

    if ($node instanceof PhpParser\Node\Stmt\Switch_) {
      foreach ($node->cases as $i => $case) {
        if (!$case->stmts) {
          continue;
        }

        $next_case = idx($node->cases, $i + 1);
        // Comments at the end of a case are perceived to belong to
        // the next case, unless there are no more cases.
        if ($next_case) {
          $comments = $next_case->getComments();
        } else {
          $comments = $case->getComments();
        }
        foreach ($comments as $comment) {
          // Liberally match "fall" in the comment text so that comments like
          // "fallthru", "fall through", "fallthrough", etc., are accepted.
          if (preg_match('/fall/i', $comment->getText())) {
            continue 2;
          }
        }

        $case_stmts_ast = PhpParserAst::newPartialAst($case->stmts);

        $breaks_and_continues = $case_stmts_ast->findNodesOfKinds(
          array(
            PhpParser\Node\Stmt\Break_::class,
            PhpParser\Node\Stmt\Continue_::class,
          ),
          array(
            PhpParser\Node\Stmt\ClassLike::class,
            PhpParser\Node\FunctionLike::class,
            // This causes false positives for things like break 2,
            // but those constructions are not recommended.
            // Such cases can be fixed by adding an unreachable "break;"
            // at the end of the case.
            PhpParser\Node\Stmt\Do_::class,
            PhpParser\Node\Stmt\While_::class,
            PhpParser\Node\Stmt\For_::class,
            PhpParser\Node\Stmt\Foreach_::class,
            PhpParser\Node\Stmt\Switch_::class,
          ));

        if (count($breaks_and_continues)) {
          continue;
        }

        $returns_throws_exits = $case_stmts_ast->findNodesOfKinds(
          array(
            PhpParser\Node\Stmt\Return_::class,
            PhpParser\Node\Expr\Throw_::class,
            PhpParser\Node\Expr\Exit_::class,
          ),
          array(
            PhpParser\Node\Stmt\ClassLike::class,
            PhpParser\Node\FunctionLike::class,
          ));

        if (count($returns_throws_exits)) {
          continue;
        }

        $this->raiseLintAtNode(
          $case,
          pht(
            'This `%s` has a nonempty block which does not end '.
            'with `%s`, `%s`, `%s`, `%s` or `%s`. Did you forget to add '.
            'one of those? If you intend to fall through, add a `%s` '.
            'comment to silence this warning.',
            $case->cond ? 'case' : 'default',
            'break',
            'continue',
            'return',
            'throw',
            'exit',
            '// fallthrough'));
      }
    }
  }

}
