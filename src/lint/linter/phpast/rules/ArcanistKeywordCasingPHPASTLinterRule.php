<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\ComplexType
 * @phutil-external-symbol class PhpParser\Node\PropertyHook
 * @phutil-external-symbol class PhpParser\Node\Expr\ClassConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\ConstFetch
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\Identifier
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\NullableType
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassConst
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Stmt\Enum_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 */
final class ArcanistKeywordCasingPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 40;

  public function getLintName() {
    return pht('Keyword Conventions');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParserAst $ast, array $token_stream) {
    $keywords = array(
      T_ABSTRACT,
      T_ARRAY,
      T_ARRAY_CAST,
      T_AS,
      T_BOOL_CAST,
      T_BREAK,
      T_CALLABLE,
      T_CASE,
      T_CATCH,
      T_CLASS,
      T_CLONE,
      T_CONST,
      T_CONTINUE,
      T_DECLARE,
      T_DEFAULT,
      T_DO,
      T_DOUBLE_CAST,
      T_ECHO,
      T_ELSE,
      T_ELSEIF,
      T_EMPTY,
      T_ENDDECLARE,
      T_ENDFOR,
      T_ENDFOREACH,
      T_ENDIF,
      T_ENDSWITCH,
      T_ENDWHILE,
      T_ENUM,
      T_EVAL,
      T_EXIT,
      T_EXTENDS,
      T_FINAL,
      T_FINALLY,
      T_FN,
      T_FOR,
      T_FOREACH,
      T_FUNCTION,
      T_GLOBAL,
      T_GOTO,
      T_IF,
      T_IMPLEMENTS,
      T_INCLUDE,
      T_INCLUDE_ONCE,
      T_INSTANCEOF,
      T_INSTEADOF,
      T_INT_CAST,
      T_INTERFACE,
      T_ISSET,
      T_LIST,
      T_LOGICAL_AND,
      T_LOGICAL_OR,
      T_LOGICAL_XOR,
      T_MATCH,
      T_NAMESPACE,
      T_NEW,
      T_OBJECT_CAST,
      T_PRINT,
      T_PRIVATE,
      T_PRIVATE_SET,
      T_PROTECTED,
      T_PROTECTED_SET,
      T_PUBLIC,
      T_PUBLIC_SET,
      T_READONLY,
      T_REQUIRE,
      T_REQUIRE_ONCE,
      T_RETURN,
      T_STATIC,
      T_STRING_CAST,
      T_SWITCH,
      T_THROW,
      T_TRAIT,
      T_TRY,
      T_UNSET,
      T_UNSET_CAST,
      T_USE,
      T_VAR,
      T_VOID_CAST,
      T_WHILE,
      T_YIELD,
      T_YIELD_FROM,
    );

    $magic_constants = array(
      T_CLASS_C,
      T_METHOD_C,
      T_FUNC_C,
      T_LINE,
      T_FILE,
      T_NS_C,
      T_DIR,
      T_TRAIT_C,
      T_PROPERTY_C,
    );

    $false_positives = array();

    // Collect all method names first. You can name a method `throw` or `array`,
    // and PHP will tokenize that as T_THROW or T_ARRAY, instead of T_STRING.
    // It is still a valid method name and shouldn't be linted as a keyword.
    $methods = $ast->findNodesOfKind(
      PhpParser\Node\Stmt\ClassMethod::class);
    foreach ($methods as $method) {
      $false_positives[$method->name->getStartTokenPos()] = true;
    }

    // Also collect class consts. You can name a const `PUBLIC` or `PRIVATE`,
    // and PHP will tokenize that as T_PUBLIC or T_PRIVATE, instead of T_STRING.
    // It is still a valid const name and shouldn't be linted as a keyword.
    $class_consts = $ast->findNodesOfKind(
      PhpParser\Node\Stmt\ClassConst::class);
    foreach ($class_consts as $class_const) {
      foreach ($class_const->consts as $const) {
        $false_positives[$const->name->getStartTokenPos()] = true;
      }
    }

    // Finally collect class const fetches, which behave the same.
    $class_const_fetches = $ast->findNodesOfKind(
      PhpParser\Node\Expr\ClassConstFetch::class);
    foreach ($class_const_fetches as $class_const_fetch) {
      if ($class_const_fetch->name instanceof PhpParser\Node\Identifier) {
        $false_positives[$class_const_fetch->name->getStartTokenPos()] = true;
      }
    }

    foreach ($token_stream as $i => $token) {
      if ($token->is($keywords)) {
        if ($token->text === strtolower($token->text)) {
          continue;
        }

        if (isset($false_positives[$i])) {
          continue;
        }

        $this->raiseLintAtToken(
          $token,
          pht(
            'Convention: spell keyword `%s` as `%s`.',
            $token->text,
            strtolower($token->text)),
          strtolower($token->text));
      } else if ($token->is($magic_constants)) {
        if ($token->text === strtoupper($token->text)) {
          continue;
        }

        $this->raiseLintAtToken(
          $token,
          pht('Magic constants should be uppercase.'),
          strtoupper($token->text));
      }
    }

    $consts = $ast->findNodesOfKind(
      PhpParser\Node\Expr\ConstFetch::class);

    foreach ($consts as $const) {
      $name = $const->name->toLowerString();
      switch ($name) {
        case 'false':
        case 'true':
        case 'null':
          if ($const->name->toString() !== $name) {
            $this->raiseLintAtNode(
              $const,
              pht(
                'Convention: spell keyword `%s` as `%s`.',
                $const->name->toString(),
                $name),
              $name,
              $token_stream);
          }
          break;
      }
    }

    foreach ($ast->findStaticAccess() as $class_static_access => $in_closure) {
      if (!($class_static_access->class instanceof PhpParser\Node\Name)) {
        continue;
      }

      $name = $class_static_access->class->toString();

      if (
        $class_static_access->class->isSpecialClassName() &&
        $name !== $class_static_access->class->toLowerString()) {

        $this->raiseLintAtNode(
          $class_static_access->class,
          pht(
            'Convention: spell keyword `%s` as `%s`.',
            $name,
            $class_static_access->class->toLowerString()),
          $class_static_access->class->toLowerString(),
          $token_stream);
      }
    }

    $function_likes = $ast->findNodesOfKind(
      PhpParser\Node\FunctionLike::class);

    foreach ($function_likes as $function_like) {
      foreach ($function_like->getParams() as $param) {
        $this->lintTypeKeywords($param->type, $token_stream);
      }

      $this->lintTypeKeywords($function_like->getReturnType(), $token_stream);
    }

    $typed_members = $ast->findNodesOfKinds(
      array(
        PhpParser\Node\Stmt\Property::class,
        PhpParser\Node\Stmt\ClassConst::class,
      ));

    foreach ($typed_members as $typed_member) {
      $this->lintTypeKeywords($typed_member->type, $token_stream);
    }

    $property_hooks = $ast->findNodesOfKind(
      PhpParser\Node\PropertyHook::class);

    foreach ($property_hooks as $property_hook) {
      $keyword = $property_hook->name->toString();
      if ($property_hook->name->toLowerString() !== $keyword) {
        $this->raiseLintAtNode(
          $property_hook->name,
          pht(
            'Convention: spell keyword `%s` as `%s`.',
            $keyword,
            $property_hook->name->toLowerString()),
          $property_hook->name->toLowerString(),
          $token_stream);
      }
    }

    $enums = $ast->findNodesOfKind(PhpParser\Node\Stmt\Enum_::class);

    foreach ($enums as $enum) {
      if (!$enum->scalarType) {
        continue;
      }

      $this->lintTypeKeywords($enum->scalarType, $token_stream);
    }
  }

  /**
   * @param PhpParser\Node|null $type
   * @param array<PhpParser\Token> $token_stream
   * @return void
   */
  private function lintTypeKeywords($type, array $token_stream) {
    foreach ($this->unnestTypeHints($type) as $type_hint) {
      if ($type_hint instanceof PhpParser\Node\Identifier) {
        switch ($type_hint->toLowerString()) {
          case 'array':
          case 'callable':
          case 'string':
          case 'float':
          case 'int':
          case 'bool':
          case 'self':
          case 'parent':
          case 'void':
          case 'iterable':
          case 'object':
          case 'static':
          case 'mixed':
          case 'never':
          case 'null':
          case 'false':
          case 'true':
            break;
          default:
            continue 2;
        }

        // Identifiers are always made lowercase by PHP-Parser,
        // and we need to look at the token stream to see how they're
        // actually spelled.
        $actual = $this->getString($type_hint, $token_stream);

        if ($actual !== $type_hint->toLowerString()) {
          $this->raiseLintAtNode(
            $type_hint,
            pht(
              'Convention: spell keyword `%s` as `%s`.',
              $type_hint->toString(),
              $type_hint->toLowerString()),
            $type_hint->toLowerString(),
            $token_stream);
        }
      }
    }
  }

  /**
   * @param null|PhpParser\Node $type
   * @return Generator
   */
  private function unnestTypeHints($type): Generator {
    if (
      $type instanceof PhpParser\Node\Identifier ||
      $type instanceof PhpParser\Node\Name) {

      yield $type;
    } else if ($type instanceof PhpParser\Node\ComplexType) {
      if ($type instanceof PhpParser\Node\NullableType) {
        $types = array($type->type);
      } else {
        $types = $type->types;
      }

      foreach ($types as $nested_type) {
        foreach ($this->unnestTypeHints($nested_type) as $type_hint) {
          yield $type_hint;
        }
      }
    }
  }

}
