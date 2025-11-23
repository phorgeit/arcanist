<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassConst
 * @phutil-external-symbol class PhpParser\Node\Stmt\Const_
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Param
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Enum_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Interface_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Trait_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Function_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassConst
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\Const
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 * @phutil-external-symbol class PhpParser\Node\Stmt\Global_
 */
final class ArcanistNamingConventionsPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 9;

  private $naminghook;

  public function getLintName() {
    return pht('Naming Conventions');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function getLinterConfigurationOptions() {
    return parent::getLinterConfigurationOptions() + array(
      'phpast.naminghook' => array(
        'type' => 'optional string',
        'help' => pht(
          'Name of a concrete subclass of `%s` which enforces more '.
          'granular naming convention rules for symbols.',
          ArcanistPHPASTLintNamingHook::class),
      ),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'phpast.naminghook':
        $this->naminghook = $value;
        return;

      default:
        parent::setLinterConfigurationValue($key, $value);
        return;
    }
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    $named_nodes = $ast->findNodesOfKinds(
      array(
        PhpParser\Node\Stmt\ClassLike::class,
        PhpParser\Node\FunctionLike::class,
        PhpParser\Node\Stmt\Property::class,
        PhpParser\Node\Stmt\ClassConst::class,
        PhpParser\Node\Stmt\Const_::class,
        PhpParser\Node\Expr\FuncCall::class,
        PhpParser\Node\Param::class,
      ));

    $superglobal_map = array_fill_keys(
      $this->getSuperGlobalNames(),
      true);

    $names = array();

    // We're going to build up a list of <type, name, token, error> tuples
    // and then try to instantiate a hook class which has the opportunity to
    // override us.
    foreach ($named_nodes as $node) {
      if ($node instanceof PhpParser\Node\Stmt\Class_) {
        if (!$node->name) {
          continue;
        }

        $names[] = array(
          'class',
          $node->name->toString(),
          $node->name,
          ArcanistPHPASTLintNamingHook::isUpperCamelCase(
            $node->name->toString())
            ? null
            : pht(
              'Follow naming conventions: classes should be named using `%s`.',
              'UpperCamelCase'),
        );
      } else if ($node instanceof PhpParser\Node\Stmt\Interface_) {
        $names[] = array(
          'interface',
          $node->name->toString(),
          $node->name,
          ArcanistPHPASTLintNamingHook::isUpperCamelCase(
            $node->name->toString())
            ? null
            : pht(
              'Follow naming conventions: interfaces should be named using '.
              '`%s`.',
              'UpperCamelCase'),
        );
      } else if ($node instanceof PhpParser\Node\Stmt\Trait_) {
        $names[] = array(
          'trait',
          $node->name->toString(),
          $node->name,
          ArcanistPHPASTLintNamingHook::isUpperCamelCase(
            $node->name->toString())
            ? null
            : pht(
              'Follow naming conventions: traits should be named using `%s`.',
              'UpperCamelCase'),
        );
      } else if ($node instanceof PhpParser\Node\Stmt\Enum_) {
        $names[] = array(
          'enum',
          $node->name->toString(),
          $node->name,
          ArcanistPHPASTLintNamingHook::isUpperCamelCase(
            $node->name->toString())
            ? null
            : pht(
              'Follow naming conventions: enums should be named using `%s`.',
              'UpperCamelCase'),
        );
      } else if ($node instanceof PhpParser\Node\Stmt\Function_) {
        $names[] = array(
          'function',
          $node->name->toString(),
          $node->name,
          ArcanistPHPASTLintNamingHook::isLowercaseWithUnderscores(
            ArcanistPHPASTLintNamingHook::stripPHPFunction(
              $node->name->toString()))
            ? null
            : pht(
              'Follow naming conventions: functions should be named using '.
              '`%s`.',
              'lowercase_with_underscores'),
          );
      } else if ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
        $names[] = array(
          'method',
          $node->name->toString(),
          $node->name,
          ArcanistPHPASTLintNamingHook::isLowerCamelCase(
            ArcanistPHPASTLintNamingHook::stripPHPFunction(
              $node->name->toString()))
            ? null
            : pht(
              'Follow naming conventions: methods should be named using `%s`.',
              'lowerCamelCase'),
        );
      } else if ($node instanceof PhpParser\Node\Stmt\Property) {
        foreach ($node->props as $prop) {
          $names[] = array(
            'member',
            $prop->name->toString(),
            $prop->name,
            ArcanistPHPASTLintNamingHook::isLowerCamelCase(
              $prop->name->toString())
              ? null
              : pht(
                'Follow naming conventions: class properties '.
                'should be named using `%s`.',
                'lowerCamelCase'),
          );
        }
      } else if ($node instanceof PhpParser\Node\Stmt\ClassConst) {
        foreach ($node->consts as $const) {
          $names[] = array(
            'constant',
            $const->name->toString(),
            $const->name,
            ArcanistPHPASTLintNamingHook::isUppercaseWithUnderscores(
              $const->name->toString())
              ? null
              : pht(
                'Follow naming conventions: class constants '.
                'should be named using `%s`',
                'UPPERCASE_WITH_UNDERSCORES'),
          );
        }
      } else if ($node instanceof PhpParser\Node\Stmt\Const_) {
        foreach ($node->consts as $const) {
          $names[] = array(
            'constant',
            $const->name->toString(),
            $const->name,
            ArcanistPHPASTLintNamingHook::isUppercaseWithUnderscores(
              $const->name->toString())
              ? null
              : pht(
                  'Follow naming conventions: constants '.
                  'should be named using `%s`.',
                  'UPPERCASE_WITH_UNDERSCORES'),
          );
        }
      } else if ($node instanceof PhpParser\Node\Expr\FuncCall) {
        if (
          !($node->name instanceof PhpParser\Node\Name) ||
          $node->name->toLowerString() !== 'define' ||
          count($node->args) < 2 ||
          !($node->args[0]->value instanceof PhpParser\Node\Scalar\String_)) {

          continue;
        }

        $names[] = array(
          'constant',
          $node->args[0]->value->value,
          $node->args[0]->value,
          ArcanistPHPASTLintNamingHook::isUppercaseWithUnderscores(
            $node->args[0]->value->value)
            ? null
            : pht(
                'Follow naming conventions: constants '.
                'should be named using `%s`.',
                'uppercase_with_underscores'),
        );
      }

      if ($node instanceof PhpParser\Node\FunctionLike) {
        $globals_map = array();
        $parameters_map = array();

        $globals = PhpParserAst::newPartialAst($node->getStmts() ?? array())
          ->findNodesOfKind(PhpParser\Node\Stmt\Global_::class);

        foreach ($globals as $global) {
          foreach ($global->vars as $var) {
            if (
              !($var instanceof PhpParser\Node\Expr\Variable) ||
              !is_string($var->name)) {
              continue;
            }

            $names[] = array(
              'user',
              $var->name,
              $var,

              // No advice for globals,
              // but hooks have an option to provide some.
              null,
            );

            $globals_map[$var->name] = true;
          }
        }

        foreach ($node->getParams() as $param) {
          // Promoted properties are named like properties, not parameters.
          if ($param->isPromoted()) {
            $names[] = array(
              'member',
              $param->var->name,
              $param->var,
              ArcanistPHPASTLintNamingHook::isLowerCamelCase(
                $param->var->name)
                ? null
                : pht(
                  'Follow naming conventions: class properties '.
                  'should be named using `%s`.',
                  'lowerCamelCase'),
            );
          } else if ($param->var instanceof PhpParser\Node\Expr\Variable) {
            $names[] = array(
              'parameter',
              $param->var->name,
              $param->var,
              ArcanistPHPASTLintNamingHook::isLowercaseWithUnderscores(
                $param->var->name)
                ? null
                : pht(
                  'Follow naming conventions: parameters '.
                  'should be named using `%s`',
                  'lowercase_with_underscores'),
            );
          }

          $parameters_map[$param->var->name] = true;
        }

        $variables = PhpParserAst::newPartialAst($node->getStmts() ?? array())
          ->findVariablesInScope();

        foreach ($variables as $variable) {
          if (
            isset($parameters_map[$variable->name]) ||
            isset($superglobal_map[$variable->name]) ||
            isset($globals_map[$variable->name])) {
            continue;
          }

          $names[] = array(
            'variable',
            $variable->name,
            $variable,
            ArcanistPHPASTLintNamingHook::isLowercaseWithUnderscores(
              $variable->name)
                ? null
                : pht(
                  'Follow naming conventions: variables '.
                  'should be named using `%s`.',
                  'lowercase_with_underscores'),
          );
        }
      }
    }

    // If a naming hook is configured, give it a chance to override the
    // default results for all the symbol names.
    if ($this->naminghook) {
      $hook_obj = newv($this->naminghook, array());
      foreach ($names as $k => $name_attrs) {
        list($type, $name, $node, $default) = $name_attrs;
        $result = $hook_obj->lintSymbolName($type, $name, $default);
        $names[$k][3] = $result;
      }
    }

    // Raise anything we're left with.
    foreach ($names as $name_attrs) {
      list($type, $name, $node, $result) = $name_attrs;
      if ($result) {
        $this->raiseLintAtNode($node, $result);
      }
    }
  }

}
