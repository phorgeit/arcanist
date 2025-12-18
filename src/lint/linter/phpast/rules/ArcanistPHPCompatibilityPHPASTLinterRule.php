<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr\ClassConstFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\FuncCall
 * @phutil-external-symbol class PhpParser\Node\Identifier
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassLike
 * @phutil-external-symbol class PhpParser\Node\Stmt\Enum_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Class_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Interface_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Trait_
 * @phutil-external-symbol class PhpParser\Node\Expr\New_
 * @phutil-external-symbol class PhpParser\Node\Stmt\TraitUse
 * @phutil-external-symbol class PhpParser\Node\Expr\ConstFetch
 * @phutil-external-symbol class PhpParser\Node\Scalar\MagicConst
 * @phutil-external-symbol class PhpParser\Node\Expr\Closure
 * @phutil-external-symbol class PhpParser\Node\Stmt\Namespace_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Use_
 * @phutil-external-symbol class PhpParser\Node\Expr\StaticCall
 * @phutil-external-symbol class PhpParser\Node\Expr\Ternary
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrayDimFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\CallLike
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 * @phutil-external-symbol class PhpParser\Node\Expr\MethodCall
 * @phutil-external-symbol class PhpParser\Node\Expr\NullsafeMethodCall
 * @phutil-external-symbol class PhpParser\Node\Expr\PropertyFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\NullsafePropertyFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\Variable
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Break_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Continue_
 * @phutil-external-symbol class PhpParser\Node\Expr\Yield_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Finally_
 * @phutil-external-symbol class PhpParser\Node\Stmt\Foreach_
 * @phutil-external-symbol class PhpParser\Node\Expr\List_
 * @phutil-external-symbol class PhpParser\Node\Expr\Empty_
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassConst
 * @phutil-external-symbol class PhpParser\Node\Param
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Pow
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignOp\Pow
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Coalesce
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Spaceship
 * @phutil-external-symbol class PhpParser\Node\Stmt\GroupUse
 * @phutil-external-symbol class PhpParser\Node\Expr\YieldFrom
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignOp\ShiftLeft
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\ShiftLeft
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignOp\ShiftRight
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\ShiftRight
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp
 * @phutil-external-symbol class PhpParser\Node\Expr\UnaryMinus
 * @phutil-external-symbol class PhpParser\Node\NullableType
 * @phutil-external-symbol class PhpParser\Modifiers
 * @phutil-external-symbol class PhpParser\Node\Stmt\Catch_
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrowFunction
 * @phutil-external-symbol class PhpParser\Node\Stmt\Property
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignOp\Coalesce
 * @phutil-external-symbol class PhpParser\Node\Scalar\Float_
 * @phutil-external-symbol class PhpParser\Node\Stmt\ClassMethod
 * @phutil-external-symbol class PhpParser\Node\Expr\Throw_
 * @phutil-external-symbol class PhpParser\Node\UnionType
 * @phutil-external-symbol class PhpParser\Node\Expr\Match_
 * @phutil-external-symbol class PhpParser\Node\Expr\NullsafeMethodCall
 * @phutil-external-symbol class PhpParser\Node\Expr\NullsafePropertyFetch
 * @phutil-external-symbol class PhpParser\Node\IntersectionType
 * @phutil-external-symbol class PhpParser\Node\Expr\MethodCall
 * @phutil-external-symbol class PhpParser\Node\Expr\PropertyFetch
 * @phutil-external-symbol class PhpParser\Node\Expr\Cast\Void_
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Pipe
 * @phutil-external-symbol class PhpParser\Node\StaticVar
 * @phutil-external-symbol class PhpParser\Node\FunctionLike
 * @phutil-external-symbol class PhpParser\Node\Const_
 * @phutil-external-symbol class PhpParser\Node\Stmt\If_
 */
final class ArcanistPHPCompatibilityPHPASTLinterRule
  extends ArcanistPHPASTTreeLinterRule {

  const ID = 45;

  public function getLintName() {
    return pht('PHP Compatibility');
  }

  public function process(
    PhpParserAst $ast,
    array $token_stream) {
    static $compat_info;

    if (!$this->version) {
      return;
    }

    if ($compat_info === null) {
      $target = phutil_get_library_root('arcanist').
        '/../resources/php/symbol-information.json';
      $compat_info = phutil_json_decode(Filesystem::readFile($target));
    }

    // Create a whitelist for symbols which are being used conditionally.
    $whitelist = array(
      'class'     => array(),
      'interface' => array(),
      'trait'     => array(),
      'function'  => array(),
      'constant'  => array(),
    );

    $conditionals = $ast->findNodesOfKind(
      PhpParser\Node\Stmt\If_::class);

    foreach ($conditionals as $conditional) {
      if (
        !($conditional->cond instanceof PhpParser\Node\Expr\FuncCall) ||
        !($conditional->cond->name instanceof PhpParser\Node\Name) ||
        !$conditional->cond->args) {
        continue;
      }

      $function_name = $conditional->cond->name->toLowerString();

      switch ($function_name) {
        case 'class_exists':
        case 'function_exists':
        case 'interface_exists':
        case 'trait_exists':
        case 'enum_exists':
        case 'defined':
          $type = null;
          switch ($function_name) {
            case 'enum_exists':
            case 'class_exists':
              $type = 'class';
              break;

            case 'function_exists':
              $type = 'function';
              break;

            case 'interface_exists':
              $type = 'interface';
              break;

            case 'trait_exists':
              $type = 'trait';
              break;

            case 'defined':
              $type = 'constant';
              break;
          }

          $symbol = head($conditional->cond->args)->value;

          if ($symbol instanceof PhpParser\Node\Scalar\String_) {
            $symbol_name = $symbol->value;
          } else if (
            $symbol instanceof PhpParser\Node\Expr\ClassConstFetch &&
            $symbol->class instanceof PhpParser\Node\Name &&
            $symbol->name instanceof PhpParser\Node\Identifier &&
            $symbol->name->toLowerString() === 'class') {

            $symbol_name = $symbol->class->toString();
          } else {
            break;
          }

          if (!idx($whitelist[$type], $symbol_name)) {
            $whitelist[$type][$symbol_name] = array();
          }

          $span = $conditional->stmts;

          $whitelist[$type][$symbol_name][] = range(
            head($span)->getStartTokenPos(),
            last($span)->getEndTokenPos());
          break;
      }
    }

    $calls = $ast->findNodesOfKind(PhpParser\Node\Expr\FuncCall::class);

    foreach ($calls as $call) {
      if (!($call->name instanceof PhpParser\Node\Name)) {
        continue;
      }
      $name = $call->name->toString();

      $version = idx($compat_info['functions'], $name, array());
      $min = idx($version, 'php.min');
      $max = idx($version, 'php.max');

      $whitelisted = false;
      foreach (idx($whitelist['function'], $name, array()) as $range) {
        $call_token_range = range(
          $call->getStartTokenPos(),
          $call->getEndTokenPos());

        if (array_intersect($range, $call_token_range)) {
          $whitelisted = true;
          break;
        }
      }

      if ($whitelisted) {
        continue;
      }

      if ($min && version_compare($min, $this->version, '>')) {
        $this->raiseLintAtNode(
          $call,
          pht(
            'This codebase targets PHP %s, but `%s()` was not '.
            'introduced until PHP %s.',
            $this->version,
            $name,
            $min));
      } else if ($max && version_compare($max, $this->version, '<')) {
        $this->raiseLintAtNode(
          $call,
          pht(
            'This codebase targets PHP %s, but `%s()` was '.
            'removed in PHP %s.',
            $this->version,
            $name,
            $max));
      } else if (array_key_exists($name, $compat_info['params'])) {
        foreach ($call->args as $i => $arg) {
          $version = idx($compat_info['params'][$name], $i);
          if ($version && version_compare($version, $this->version, '>')) {
            $this->raiseLintAtNode(
              $arg,
              pht(
                'This codebase targets PHP %s, but parameter %d '.
                'of `%s()` was not introduced until PHP %s.',
                $this->version,
                $i + 1,
                $name,
                $version));
          }
        }
      }

      if ($this->windowsVersion) {
        $windows = idx($compat_info['functions_windows'], $name);

        if ($windows === null) {
          // This function has no special Windows considerations.
        } else if ($windows === false) {
          $this->raiseLintAtNode(
            $call,
            pht(
              'This codebase targets PHP %s on Windows, '.
              'but `%s()` is not available there.',
              $this->windowsVersion,
              $name));
        } else if (version_compare($windows, $this->windowsVersion, '>')) {
          $this->raiseLintAtNode(
            $call,
            pht(
              'This codebase targets PHP %s on Windows, '.
              'but `%s()` is not available there until PHP %s.',
              $this->windowsVersion,
              $name,
              $windows));
        }
      }
    }

    $interfaces = array();
    $enums_or_classes = array();
    $class_likes = $ast->findNodesOfKind(PhpParser\Node\Stmt\ClassLike::class);
    foreach ($class_likes as $class_like) {
      if ($class_like instanceof PhpParser\Node\Stmt\Enum_) {
        foreach ($class_like->implements as $interface) {
          $interfaces[] = $interface;
        }
      } else if ($class_like instanceof PhpParser\Node\Stmt\Class_) {
        foreach ($class_like->implements as $interface) {
          $interfaces[] = $interface;
        }

        if ($class_like->extends) {
          $enums_or_classes[] = $class_like->extends;
        }
      } else if ($class_like instanceof PhpParser\Node\Stmt\Interface_) {
        foreach ($class_like->extends as $interface) {
          $interfaces[] = $interface;
        }
      }
    }

    $news = $ast->findNodesOfKind(PhpParser\Node\Expr\New_::class);
    foreach ($news as $new) {
      if (
        !($new->class instanceof PhpParser\Node\Name) ||
        $new->class->isSpecialClassName()) {

        continue;
      }

      $enums_or_classes[] = $new->class;
    }

    $this->lintTypeCompatibility(
      $compat_info['classes'],
      $whitelist,
      $enums_or_classes,
      'class');
    $this->lintTypeCompatibility(
      $compat_info['interfaces'],
      $whitelist,
      $interfaces,
      'interface');

    $traits = array();
    $trait_uses = $ast->findNodesOfKind(PhpParser\Node\Stmt\TraitUse::class);
    foreach ($trait_uses as $trait_use) {
      foreach ($trait_use->traits as $trait) {
        $traits[] = $trait;
      }
    }

    $this->lintTypeCompatibility(
      $compat_info['traits'] ?? array(),
      $whitelist,
      $traits,
      'trait');

    $constants = $ast->findNodesOfKind(
      PhpParser\Node\Expr\ConstFetch::class);
    foreach ($constants as $constant) {
      $this->lintConstantCompatibility(
        $constant->name->toString(),
        $constant,
        $whitelist,
        $compat_info);
    }

    $magic_constants = $ast->findNodesOfKind(
      PhpParser\Node\Scalar\MagicConst::class);
    foreach ($magic_constants as $magic_constant) {
      $this->lintConstantCompatibility(
        $magic_constant->getName(),
        $magic_constant,
        $whitelist,
        $compat_info);
    }

    if (version_compare($this->version, '5.3.0') < 0) {
      $this->lintPHP53Features($ast, $token_stream);
    } else {
      $this->lintPHP53Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '5.4.0') < 0) {
      $this->lintPHP54Features($ast, $token_stream);
    } else {
      $this->lintPHP54Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '5.5.0') < 0) {
      $this->lintPHP55Features($ast, $token_stream);
    } else {
      $this->lintPHP55Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '5.6.0') < 0) {
      $this->lintPHP56Features($ast, $token_stream);
    } else {
      $this->lintPHP56Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '7.0.0') < 0) {
      $this->lintPHP70Features($ast, $token_stream);
    } else {
      $this->lintPHP70Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '7.1.0') < 0) {
      $this->lintPHP71Features($ast, $token_stream);
    } else {
      $this->lintPHP71Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '7.2.0') < 0) {
      $this->lintPHP72Features($ast, $token_stream);
    } else {
      $this->lintPHP72Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '7.3.0') < 0) {
      $this->lintPHP73Features($ast, $token_stream);
    } else {
      $this->lintPHP73Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '7.4.0') < 0) {
      $this->lintPHP74Features($ast, $token_stream);
    } else {
      $this->lintPHP74Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '8.0.0') < 0) {
      $this->lintPHP80Features($ast, $token_stream);
    } else {
      $this->lintPHP80Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '8.1.0') < 0) {
      $this->lintPHP81Features($ast, $token_stream);
    } else {
      $this->lintPHP81Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '8.2.0') < 0) {
      $this->lintPHP82Features($ast, $token_stream);
    } else {
      $this->lintPHP82Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '8.3.0') < 0) {
      $this->lintPHP83Features($ast, $token_stream);
    } else {
      $this->lintPHP83Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '8.4.0') < 0) {
      $this->lintPHP84Features($ast, $token_stream);
    } else {
      $this->lintPHP84Incompatibilities($ast, $token_stream);
    }

    if (version_compare($this->version, '8.5.0') < 0) {
      $this->lintPHP85Features($ast, $token_stream);
    } else {
      $this->lintPHP85Incompatibilities($ast, $token_stream);
    }
  }

  private function lintPHP53Features(PhpParserAst $ast, array $token_stream) {
    $closures = $ast->findNodesOfKind(PhpParser\Node\Expr\Closure::class);
    foreach ($closures as $closure) {
      $this->raiseLintAtNode(
        $closure,
        pht(
          'This codebase targets PHP %s, but anonymous '.
          'functions were not introduced until PHP 5.3.',
          $this->version));
    }

    $namespaces = $ast->findNodesOfKind(PhpParser\Node\Stmt\Namespace_::class);
    foreach ($namespaces as $namespace) {
      $this->raiseLintAtNode(
        $namespace,
        pht(
          'This codebase targets PHP %s, but namespaces were not '.
          'introduced until PHP 5.3.',
          $this->version));
    }

    $uses = $ast->findNodesOfKind(PhpParser\Node\Stmt\Use_::class);
    foreach ($uses as $use) {
      $this->raiseLintAtNode(
        $use,
        pht(
          'This codebase targets PHP %s, but namespaces were not '.
          'introduced until PHP 5.3.',
          $this->version));
    }

    $statics = $ast->findNodesOfKind(PhpParser\Node\Expr\StaticCall::class);
    foreach ($statics as $static) {
      if (
        $static->class instanceof PhpParser\Node\Name &&
        $static->class->isSpecialClassName() &&
        $static->class->toLowerString() === 'static') {
        $this->raiseLintAtNode(
          $static->class,
          pht(
            'This codebase targets PHP %s, but `%s` was not '.
            'introduced until PHP 5.3.',
            $this->version,
            'static::'));
      }
    }

    $ternaries = $ast->findNodesOfKind(PhpParser\Node\Expr\Ternary::class);
    foreach ($ternaries as $ternary) {
      if (!$ternary->if) {
        $this->raiseLintAtNode(
          $ternary,
          pht(
            'This codebase targets PHP %s, but short ternary was '.
            'not introduced until PHP 5.3.',
            $this->version));
      }
    }

    $strings = $ast->findNodesOfKind(PhpParser\Node\Scalar\String_::class);
    foreach ($strings as $string) {
      $kind = $string->getAttribute('kind');
      if ($kind === PhpParser\Node\Scalar\String_::KIND_NOWDOC) {
        $this->raiseLintAtNode(
          $string,
          pht(
            'This codebase targets PHP %s, but nowdoc was not '.
            'introduced until PHP 5.3.',
            $this->version));
      }
    }
  }

  private function lintPHP53Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {}

  private function lintPHP54Features(PhpParserAst $ast, array $token_stream) {
    $indexes = $ast->findNodesOfKind(
      PhpParser\Node\Expr\ArrayDimFetch::class);

    foreach ($indexes as $index) {
      if ($index->var instanceof PhpParser\Node\Expr\CallLike) {
        $this->raiseLintAtNode(
          $index->dim ?? $index->var,
          pht(
            'The `%s` syntax was not introduced until PHP 5.4, but this '.
            'codebase targets an earlier version of PHP. You can rewrite '.
            'this expression using `%s`.',
            'f()[...]',
            'idx()'));
      }
    }

    $arrays = $ast->findNodesOfKind(PhpParser\Node\Expr\Array_::class);
    foreach ($arrays as $array) {
      $kind = $array->getAttribute('kind');
      if ($kind === PhpParser\Node\Expr\Array_::KIND_SHORT) {
        $this->raiseLintAtNode(
          $array,
          pht(
            'The short array syntax ("[...]") was not introduced until '.
            'PHP 5.4, but this codebase targets an earlier version of PHP. '.
            'You can rewrite this expression using `array(...)` instead.'));
      }
    }

    $closures = $ast->findNodesOfKind(PhpParser\Node\Expr\Closure::class);

    foreach ($closures as $closure) {
      $static_accesses = PhpParserAst::newPartialAst($closure->stmts)
        ->findNodesOfKind(PhpParser\Node\Expr\StaticCall::class);

      foreach ($static_accesses as $static) {
        if (
          $static->class->isSpecialClassName() &&
          $static->class->toLowerString() === 'self') {

          $this->raiseLintAtNode(
            $static->class,
            pht(
              'The use of `%s` in an anonymous closure is not '.
              'available before PHP 5.4.',
              'self'));
        }
      }

      $member_accesses = $ast->findNodesOfKinds(
        array(
          PhpParser\Node\Expr\MethodCall::class,
          PhpParser\Node\Expr\NullsafeMethodCall::class,
          PhpParser\Node\Expr\PropertyFetch::class,
          PhpParser\Node\Expr\NullsafePropertyFetch::class,
        ));
      foreach ($member_accesses as $member_access) {
        $var = $member_access->var;

        if (
          $var instanceof PhpParser\Node\Expr\Variable &&
          $var->name === 'this') {

          $this->raiseLintAtNode(
            $var,
            pht(
              'The use of `%s` in an anonymous closure is not '.
              'available before PHP 5.4.',
              '$this'));
        } else if (
          $var instanceof PhpParser\Node\Expr\New_ &&
          $var->getStartTokenPos() !== $member_access->getStartTokenPos() &&
          $token_stream[$member_access->getStartTokenPos()]->is('(')) {

          $this->raiseLintAtNode(
            $member_access,
            pht(
              'Class member access on instantiation was not '.
              'introduced until PHP 5.4, but this codebase targets an '.
              'earlier version of PHP. You can rewrite this expression '.
              'using `%s`.',
              'id()'));
        }
      }
    }

    $integers = $ast->findNodesOfKind(PhpParser\Node\Scalar\Int_::class);
    foreach ($integers as $integer) {
      $kind = $integer->getAttribute('kind');
      if ($kind === PhpParser\Node\Scalar\Int_::KIND_BIN) {
        $this->raiseLintAtNode(
          $integer,
          pht(
            'Binary integer literals are not available before PHP 5.4.'));
      }
    }
  }

  private function lintPHP54Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {
    $breaks = $ast->findNodesOfKinds(
      array(
        PhpParser\Node\Stmt\Break_::class,
        PhpParser\Node\Stmt\Continue_::class,
      ));

    foreach ($breaks as $break) {
      if (
        !$break->num ||
        $break->num instanceof PhpParser\Node\Scalar\Int_) {

        continue;
      }

      $this->raiseLintAtNode(
        $break->num,
        pht(
          'The `%s` and `%s` statements no longer accept '.
          'variable arguments in PHP 5.4.',
          'break',
          'continue'));
    }
  }

  private function lintPHP55Features(PhpParserAst $ast, array $token_stream) {
    $yields = $ast->findNodesOfKind(PhpParser\Node\Expr\Yield_::class);

    foreach ($yields as $yield) {
      $this->raiseLintAtNode(
        $yield,
        pht('Generators are not available before PHP 5.5.'));
    }

    $finallys = $ast->findNodesOfKind(PhpParser\Node\Stmt\Finally_::class);

    foreach ($finallys as $finally) {
      $this->raiseLintAtNode(
        $finally,
        pht(
          '`%s` blocks are not available before PHP 5.5.',
          'finally'));
    }

    $foreaches = $ast->findNodesOfKind(PhpParser\Node\Stmt\Foreach_::class);

    foreach ($foreaches as $foreach) {
      if ($foreach->valueVar instanceof PhpParser\Node\Expr\List_) {
        $kind = $foreach->valueVar->getAttribute('kind');
        if ($kind === PhpParser\Node\Expr\List_::KIND_LIST) {
          $variant = 'list';
        } else {
          $variant = '[...]';
        }

        $this->raiseLintAtNode(
          $foreach->valueVar,
          pht(
            'Using `%s` as the `%s` value instead of a variable was '.
            'not introduced until PHP 5.5, but this codebase targets an '.
            'earlier version of PHP. Move the statement inside the loop.',
            $variant,
            'as'));
      }
    }

    $emptys = $ast->findNodesOfKind(PhpParser\Node\Expr\Empty_::class);
    foreach ($emptys as $empty) {
      if (
        !($empty->expr instanceof PhpParser\Node\Expr\Variable) &&
        !($empty->expr instanceof PhpParser\Node\Expr\ArrayDimFetch)) {

        $this->raiseLintAtNode(
          $empty->expr,
          pht(
            'Support for arbitrary expressions in the `%s` construct was '.
            'not introduced until PHP 5.5, but this codebase targets an '.
            'earlier version of PHP.',
            'empty'));
      }
    }

    $indexes = $ast->findNodesOfKind(PhpParser\Node\Expr\ArrayDimFetch::class);
    foreach ($indexes as $index) {
      if (
        $index->var instanceof PhpParser\Node\Expr\Array_ ||
        $index->var instanceof PhpParser\Node\Scalar\String_) {
        $this->raiseLintAtNode(
          $index->dim ?? $index->var,
          pht(
            'Dereferencing array and string literals was not introduced '.
            'until PHP 5.5, but this codebase targets an earlier version of '.
            'PHP. You can rewrite this expression using `%s`.',
            'idx()'));
      }
    }

    $class_statics = $ast->findNodesOfKind(
      PhpParser\Node\Expr\ClassConstFetch::class);

    foreach ($class_statics as $class_static) {
      if (
        $class_static->name instanceof PhpParser\Node\Identifier &&
        $class_static->name->toString() === 'class') {

        $this->raiseLintAtNode(
          $class_static,
          pht(
            'Support for the class constant `%s` was not introduced until '.
            'PHP 5.5 but this codebase targets an earlier version of PHP. '.
            'You can rewrite this expression using the class name as a '.
            'string directly.',
            'class'));
      }
    }
  }

  private function lintPHP55Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {}

  private function lintPHP56Features(PhpParserAst $ast, array $token_stream) {
    $consts = $ast->findNodesOfKind(PhpParser\Node\Const_::class);

    foreach ($consts as $const) {
      if (
        $const->value instanceof PhpParser\Node\Expr\BinaryOp ||
        $const->value instanceof PhpParser\Node\Expr\ArrayDimFetch) {

        $this->raiseLintAtNode(
          $const->value,
          pht(
            'Constant expressions were not introduced until PHP 5.6, '.
            'but this codebase targets an earlier version of PHP.'));
      } else if ($const->value instanceof PhpParser\Node\Expr\Array_) {
        $this->raiseLintAtNode(
          $const->value,
          pht(
            'Defining constant arrays was not introduced until PHP 5.6, '.
            'but this codebase targets an earlier version of PHP.'));
      }
    }

    $parameters = $ast->findNodesOfKind(PhpParser\Node\Param::class);

    foreach ($parameters as $parameter) {
      if ($parameter->variadic) {
        $this->raiseLintAtNode(
          $parameter,
          pht(
            'The use of variadic arguments is not available before PHP '.
            '5.6.'));
      } else if (
        $parameter->default instanceof PhpParser\Node\Expr\BinaryOp) {
          $this->raiseLintAtNode(
            $parameter->default,
            pht(
              'Constant expressions were not introduced until PHP 5.6, '.
              'but this codebase targets an earlier version of PHP.'));
        }
    }

    $call_likes = $ast->findNodesOfKind(PhpParser\Node\Expr\CallLike::class);

    foreach ($call_likes as $call_like) {
      if ($call_like->isFirstClassCallable()) {
        continue;
      }

      foreach ($call_like->getRawArgs() as $arg) {
        if ($arg->unpack) {
          $this->raiseLintAtNode(
            $arg,
            pht('Argument unpacking is not available before PHP 5.6.'));
        }
      }
    }

    $exponentiations = $ast->findNodesOfKinds(
      array(
        PhpParser\Node\Expr\BinaryOp\Pow::class,
        PhpParser\Node\Expr\AssignOp\Pow::class,
      ));

    foreach ($exponentiations as $exponentiation) {
      $this->raiseLintAtNode(
        $exponentiation,
        pht(
          'The `%s` operator is not available before PHP 5.6.',
          '**'));
    }

    $uses = $ast->findNodesOfKind(PhpParser\Node\Stmt\Use_::class);

    foreach ($uses as $use) {
      if (
        $use->type === PhpParser\Node\Stmt\Use_::TYPE_FUNCTION ||
        $use->type === PhpParser\Node\Stmt\Use_::TYPE_CONSTANT) {

        $this->raiseLintAtNode(
          $use,
          pht(
            'Importing functions or constants is not available before '.
            'PHP 5.6.'));
      }
    }
  }

  private function lintPHP56Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {}

  private function lintPHP70Features(PhpParserAst $ast, array $token_stream) {
    $function_likes = $ast->findNodesOfKind(PhpParser\Node\FunctionLike::class);

    foreach ($function_likes as $function_like) {
      foreach ($function_like->getParams() as $param) {
        if (!($param->type instanceof PhpParser\Node\Identifier)) {
          continue;
        }

        switch ($param->type->toLowerString()) {
          case 'string':
          case 'float':
          case 'int':
          case 'bool':
            $this->raiseLintAtNode(
              $param,
              pht(
                'The `%s` type hint is not available before PHP 7.0.',
                $param->type->toLowerString()));
            break;
          default:
            continue 2;
        }
      }

      if ($function_like->getReturnType()) {
        $this->raiseLintAtNode(
          $function_like->getReturnType(),
          pht('Return type hints are not available before PHP 7.0.'));
      }
    }

    $null_coalesces = $ast->findNodesOfKind(
      PhpParser\Node\Expr\BinaryOp\Coalesce::class);

    foreach ($null_coalesces as $null_coalesce) {
      $this->raiseLintAtNode(
        $null_coalesce,
        pht(
          'The null coalescing operator is not available before PHP 7.0.'));
    }

    $spaceships = $ast->findNodesOfKind(
      PhpParser\Node\Expr\BinaryOp\Spaceship::class);

    foreach ($spaceships as $spaceship) {
      $this->raiseLintAtNode(
        $spaceship,
        pht(
          'The spaceship operator is not available before PHP 7.0.'));
    }

    $news = $ast->findNodesOfKind(PhpParser\Node\Expr\New_::class);

    foreach ($news as $new) {
      if ($new->class instanceof PhpParser\Node\Stmt\Class_) {
        $this->raiseLintAtNode(
        $new->class,
          pht(
            'Anonymous classes are not available before PHP 7.0.'));
      }
    }

    $uses = $ast->findNodesOfKind(PhpParser\Node\Stmt\GroupUse::class);

    foreach ($uses as $use) {
      $this->raiseLintAtNode(
        $use,
        pht(
          'Support for grouped use statements was not introduced '.
          'until PHP 7.0 but this codebase targets an earlier version '.
          'of PHP.'));
    }

    $yield_froms = $ast->findNodesOfKind(PhpParser\Node\Expr\YieldFrom::class);

    foreach ($yield_froms as $yield_from) {
      $this->raiseLintAtNode(
        $yield_from,
        pht(
          'Generator delegation (`%s`) is not available before PHP 7.0.',
          'yield from'));
    }
  }

  private function lintPHP70Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {
    $lists = $ast->findNodesOfKind(PhpParser\Node\Expr\List_::class);

    foreach ($lists as $list) {
      if (!$list->items || $list->items === array(null)) {
        $this->raiseLintAtNode(
          $list,
          pht(
            'Empty `%s` statements are no longer supported in PHP 7.0.',
            'list'));
      }
    }

    $shifts = $ast->findNodesOfKinds(
      array(
        PhpParser\Node\Expr\AssignOp\ShiftLeft::class,
        PhpParser\Node\Expr\BinaryOp\ShiftLeft::class,
        PhpParser\Node\Expr\AssignOp\ShiftRight::class,
        PhpParser\Node\Expr\BinaryOp\ShiftRight::class,
      ));

    foreach ($shifts as $shift) {
      if ($shift instanceof PhpParser\Node\Expr\BinaryOp) {
        $shift_value = $shift->right;
      } else {
        $shift_value = $shift->expr;
      }

      if (
        $shift_value instanceof PhpParser\Node\Expr\UnaryMinus &&
        !$shift_value->expr instanceof PhpParser\Node\Expr\UnaryMinus) {

        $this->raiseLintAtNode(
          $shift_value,
          pht(
            'The shift operators no longer accept negative shifts in '.
            'PHP 7.0.'));
      }
    }
  }

  private function lintPHP71Features(PhpParserAst $ast, array $token_stream) {
    $nullable_types = $ast->findNodesOfKind(
      PhpParser\Node\NullableType::class);

    foreach ($nullable_types as $nullable_type) {
      $this->raiseLintAtNode(
        $nullable_type,
        pht('Nullable type hints are not available before PHP 7.1.'));
    }

    $lists = $ast->findNodesOfKind(PhpParser\Node\Expr\List_::class);

    foreach ($lists as $list) {
      $kind = $list->getAttribute('kind');

      if ($kind === PhpParser\Node\Expr\List_::KIND_ARRAY) {
        $this->raiseLintAtNode(
          $list,
          pht(
            'Using short array syntax for `%s` was not introduced until '.
            'PHP 7.1, but this codebase targets an earlier version of PHP.'.
            'You can rewrite this expression using the `list(...)` instead.',
            'list'));
      }
    }

    $class_consts = $ast->findNodesOfKind(
      PhpParser\Node\Stmt\ClassConst::class);

    foreach ($class_consts as $class_const) {
      if ($class_const->flags & PhpParser\Modifiers::VISIBILITY_MASK) {
        $this->raiseLintAtNode(
          $class_const,
          pht(
            'Class constant visibility is not available before PHP 7.1.'));
      }
    }

    $function_likes = $ast->findNodesOfKind(
      PhpParser\Node\FunctionLike::class);

    foreach ($function_likes as $function_like) {
      foreach ($function_like->getParams() as $param) {
        if (!($param->type instanceof PhpParser\Node\Identifier)) {
          continue;
        }

        if ($param->type->toLowerString() === 'iterable') {
          $this->raiseLintAtNode(
            $param,
            pht(
              'The `%s` type hint is not available before PHP 7.1.',
              'iterable'));
        }
      }

      $return_type = $function_like->getReturnType();

      if (
        $return_type instanceof PhpParser\Node\Identifier &&
        $return_type->toLowerString() === 'iterable') {

        $this->raiseLintAtNode(
          $return_type,
          pht(
            'The `%s` type hint is not available before PHP 7.1.',
            'iterable'));
      }
    }

    $catches = $ast->findNodesOfKind(PhpParser\Node\Stmt\Catch_::class);

    foreach ($catches as $catch) {
      if (count($catch->types) > 1) {
        $this->raiseLintAtNode(
          head($catch->types),
          pht(
            'Specifying multiple exceptions in a catch clause is not '.
            'available before PHP 7.1.'));
      }
    }

    $lists = $ast->findNodesOfKind(PhpParser\Node\Expr\List_::class);

    foreach ($lists as $list) {
      foreach ($list->items as $item) {
        if ($item && $item->key) {
          $this->raiseLintAtNode(
            $item->key,
            pht(
              'Specifying keys when destructuring arrays is not '.
              'available before PHP 7.1.'));
        }
      }
    }
  }

  private function lintPHP71Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {}

  private function lintPHP72Features(PhpParserAst $ast, array $token_stream) {
    $function_likes = $ast->findNodesOfKind(
      PhpParser\Node\FunctionLike::class);

    foreach ($function_likes as $function_like) {
      foreach ($function_like->getParams() as $param) {
        if (!($param->type instanceof PhpParser\Node\Identifier)) {
          continue;
        }

        if ($param->type->toLowerString() === 'object') {
          $this->raiseLintAtNode(
            $param,
            pht(
              'The `%s` type hint is not available before PHP 7.2.',
              'object'));
        }
      }

      $return_type = $function_like->getReturnType();

      if (
        $return_type instanceof PhpParser\Node\Identifier &&
        $return_type->toLowerString() === 'object') {

        $this->raiseLintAtNode(
          $return_type,
          pht(
            'The `%s` type hint is not available before PHP 7.2.',
            'object'));
      }
    }

    $uses = $ast->findNodesOfKind(PhpParser\Node\Stmt\GroupUse::class);
    foreach ($uses as $use) {
      if (count($use->uses) === 1) {
        continue;
      }

      $last = last($use->uses);

      for ($i = $last->getEndTokenPos(); $i < $use->getEndTokenPos(); $i++) {
        if ($token_stream[$i]->is('}')) {
          break;
        }

        if ($token_stream[$i]->is(',')) {
          $this->raiseLintAtToken(
            $token_stream[$i],
            pht(
              'Trailing commas in group-use statements are not available '.
              'before PHP 7.2.'));
        }
      }
    }
  }

  private function lintPHP72Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {

    $function_calls = $ast->findNodesOfKind(
      PhpParser\Node\Expr\FuncCall::class);

    foreach ($function_calls as $function_call) {
      if (
        !($function_call->name instanceof PhpParser\Node\Name) ||
        $function_call->name->toLowerString() !== 'get_class' ||
        count($function_call->args) !== 1) {

        continue;
      }

      $arg = head($function_call->args);
      if (
        $arg->value instanceof PhpParser\Node\Expr\ConstFetch &&
        $arg->value->name->toLowerString() === 'null') {

        $this->raiseLintAtNode(
          $function_call,
          pht(
            'Passing null to `%s` is no longer allowed in PHP 7.2.',
            'get_class'));
      }
    }
  }

  private function lintPHP73Features(PhpParserAst $ast, array $token_stream) {
    $strings = $ast->findNodesOfKind(PhpParser\Node\Scalar\String_::class);

    foreach ($strings as $string) {
      if ($string->getAttribute('docIndentation')) {
        $this->raiseLintAtNode(
          $string,
          pht(
            'Flexible Heredoc and Nowdoc is not available before PHP 7.3.'));
      }
    }

    $lists = $ast->findNodesOfKind(PhpParser\Node\Expr\List_::class);

    foreach ($lists as $list) {
      foreach ($list->items as $item) {
        if ($item && $item->byRef) {
          $this->raiseLintAtNode(
            $item,
            pht(
              'Reference assignments in array destructuring is not '.
              'available before PHP 7.3.'));
        }
      }
    }

    $call_likes = $ast->findNodesOfKind(
      PhpParser\Node\Expr\CallLike::class);

    foreach ($call_likes as $call_like) {
      if ($call_like->isFirstClassCallable() || !$call_like->getRawArgs()) {
        continue;
      }

      $last = last($call_like->getArgs());
      $call_end = $call_like->getEndTokenPos();

      for ($i = $last->getEndTokenPos() + 1; $i < $call_end; $i++) {
        $token = $token_stream[$i];

        if ($token->is(')')) {
          break;
        }

        if ($token->is(',')) {
          $this->raiseLintAtToken(
            $token,
            pht(
              'Trailing commas in function or method calls are not '.
              'available before PHP 7.3.'));
          break;
        }
      }
    }
  }

  private function lintPHP73Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {
    // continue in switch is handled by the "Continue Inside Switch"
    // linter rule (PHPAST128)
  }

  private function lintPHP74Features(PhpParserAst $ast, array $token_stream) {
    $arrow_functions = $ast->findNodesOfKind(
      PhpParser\Node\Expr\ArrowFunction::class);

    foreach ($arrow_functions as $arrow_function) {
      $this->raiseLintAtNode(
        $arrow_function,
        pht(
          'Defining closures with arrow functions was not introduced '.
          'until PHP 7.4, but this codebase targets an earlier version of '.
          'PHP.'));
    }

    $properties = $ast->findNodesOfKind(PhpParser\Node\Stmt\Property::class);

    foreach ($properties as $property) {
      if ($property->type) {
        $this->raiseLintAtNode(
          $property,
          pht(
            'Defining typed for class properties was not introduced '.
            'until PHP 7.4, but this codebase targets an earlier version of '.
            'PHP.'));
      }
    }

    $null_coalescing_assignments = $ast->findNodesOfKind(
      PhpParser\Node\Expr\AssignOp\Coalesce::class);

    foreach ($null_coalescing_assignments as $null_coalescing_assignment) {
      $this->raiseLintAtNode(
        $null_coalescing_assignment,
        pht(
          'The null coalescing assignment operator was not introduced '.
          'until PHP 7.4, but this codebase targets an earlier version of '.
          'PHP.'));
    }

    $arrays = $ast->findNodesOfKind(PhpParser\Node\Expr\Array_::class);

    foreach ($arrays as $array) {
      foreach ($array->items as $item) {
        if ($item->unpack) {
          $this->raiseLintAtNode(
            $item,
            pht(
              'Unpacking within arrays is not available before PHP 7.4.'));
        }
      }
    }

    $numbers = $ast->findNodesOfKinds(array(
        PhpParser\Node\Scalar\Int_::class,
        PhpParser\Node\Scalar\Float_::class,
      ));

    foreach ($numbers as $number) {
      $raw_value = $number->getAttribute('rawValue', '');

      if (strpos($raw_value, '_') !== false) {
        $this->raiseLintAtNode(
          $number,
          pht(
            'Numeric literal separators are not available before PHP 7.4.'));
      }
    }

    $methods = $ast->findNodesOfKind(PhpParser\Node\Stmt\ClassMethod::class);

    foreach ($methods as $method) {
      if (!$method->stmts || $method->name->toString() !== '__toString') {
        continue;
      }

      $throws = PhpParserAst::newPartialAst($method->stmts)
        ->findNodesOfKind(PhpParser\Node\Expr\Throw_::class);

      foreach ($throws as $throw) {
        $this->raiseLintAtNode(
          $throw,
          pht(
            'Throwing an `%s` from within the `%s` method is not '.
            'allowed before PHP 7.4.',
            'Exception',
            '__toString'));
      }
    }
  }

  private function lintPHP74Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {}

  private function lintPHP80Features(PhpParserAst $ast, array $token_stream) {
    $call_likes = $ast->findNodesOfKind(PhpParser\Node\Expr\CallLike::class);

    foreach ($call_likes as $call_like) {
      if ($call_like->isFirstClassCallable()) {
        continue;
      }

      foreach ($call_like->getRawArgs() as $arg) {
        if ($arg->name) {
          $this->raiseLintAtNode(
            $arg,
            pht('Named arguments are not available before PHP 8.0.'));
        }
      }
    }

    $parameters = $ast->findNodesOfKind(PhpParser\Node\Param::class);

    foreach ($parameters as $parameter) {
      // Parameters marked final are promoted properties,
      // but that functionality is only available starting PHP 8.5.
      if (
        $parameter->isPromoted() &&
        !($parameter->flags & PhpParser\Modifiers::FINAL)) {
        $this->raiseLintAtNode(
          $parameter,
          pht('Promoted properties are not available before PHP 8.0.'));
      }

      if ($parameter->type instanceof PhpParser\Node\UnionType) {
        $this->raiseLintAtNode(
          $parameter,
          pht('Union types are not available before PHP 8.0.'));
      }

      if (
        $parameter->type instanceof PhpParser\Node\Identifier &&
        $parameter->type->toLowerString() === 'mixed') {
        $this->raiseLintAtNode(
          $parameter,
          pht(
            'The `%s` type hint is not available before PHP 8.0.',
            'mixed'));
      }
    }

    $function_likes = $ast->findNodesOfKind(
      PhpParser\Node\FunctionLike::class);

    foreach ($function_likes as $function_like) {
      $return_type = $function_like->getReturnType();

      if ($return_type instanceof PhpParser\Node\UnionType) {
        $this->raiseLintAtNode(
          $return_type,
          pht('Union types are not available before PHP 8.0.'));
      } else if (
        $return_type instanceof PhpParser\Node\Identifier &&
        $return_type->toLowerString() === 'mixed') {
        $this->raiseLintAtNode(
          $return_type,
          pht(
            'The `%s` type hint is not available before PHP 8.0.',
            'mixed'));
      }

      if ($function_like->getParams()) {
        $last = last($function_like->getParams());
        $function_end = $function_like->getEndTokenPos();

        for ($i = $last->getEndTokenPos(); $i < $function_end; $i++) {
          if ($token_stream[$i]->is(')')) {
            break;
          }

          if ($token_stream[$i]->is(',')) {
            $this->raiseLintAtToken(
              $token_stream[$i],
              pht(
                'Trailing commas in parameter lists are not available '.
                'before PHP 8.0.'));
          }
        }
      }
    }

    $properties = $ast->findNodesOfKind(PhpParser\Node\Stmt\Property::class);

    foreach ($properties as $property) {
      if ($property->type instanceof PhpParser\Node\UnionType) {
        $this->raiseLintAtNode(
          $property->type,
          pht('Union types are not available before PHP 8.0.'));
      } else if (
        $property->type instanceof PhpParser\Node\Identifier &&
        $property->type->toLowerString() === 'mixed') {
        $this->raiseLintAtNode(
          $property,
          pht(
            'The `%s` type hint is not available before PHP 8.0.',
            'mixed'));
      }
    }

    $matches = $ast->findNodesOfKind(PhpParser\Node\Expr\Match_::class);

    foreach ($matches as $match) {
      $this->raiseLintAtNode(
        $match,
        pht('Match expressions are not available before PHP 8.0.'));
    }

    $null_safe_operators = $ast->findNodesOfKinds(
      array(
        PhpParser\Node\Expr\NullsafeMethodCall::class,
        PhpParser\Node\Expr\NullsafePropertyFetch::class,
      ));

    foreach ($null_safe_operators as $null_safe_operator) {
      $this->raiseLintAtNode(
        $null_safe_operator,
        pht('Nullsafe operators are not available before PHP 8.0.'));
    }

    $catches = $ast->findNodesOfKind(PhpParser\Node\Stmt\Catch_::class);

    foreach ($catches as $catch) {
      if (!$catch->var) {
        $this->raiseLintAtNode(
          $catch,
          pht(
            'Omitting the variable in a catch clause is not allowed '.
            'before PHP 8.0.'));
      }
    }

    $traits = $ast->findNodesOfKind(PhpParser\Node\Stmt\Trait_::class);

    foreach ($traits as $trait) {
      foreach ($trait->getMethods() as $method) {
        if ($method->isPrivate()) {
          $this->raiseLintAtNode(
            $method,
            pht(
              'Private methods in traits are not available before PHP 8.0.'));
        }
      }
    }
  }

  private function lintPHP80Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {
    $function_calls = $ast->findNodesOfKind(
      PhpParser\Node\Expr\FuncCall::class);

    foreach ($function_calls as $function_call) {
      if (
        $function_call->name instanceof PhpParser\Node\Name &&
        $function_call->name->toLowerString() === 'define' &&
        count($function_call->args) === 3) {

        $case_insensitive = last($function_call->args)->value;

        if (
          !($case_insensitive instanceof PhpParser\Node\Expr\ConstFetch) ||
          $case_insensitive->name->toLowerString() !== 'false') {

          $this->raiseLintAtNode(
            $case_insensitive,
            pht(
              'Passing any other value than `%s` to the third parameter '.
              'of `%s` is no longer supported in PHP 8.0.',
              'true',
              'define'));
        }
      }
    }
  }

  private function lintPHP81Features(PhpParserAst $ast, array $token_stream) {
    $ints = $ast->findNodesOfKind(PhpParser\Node\Scalar\Int_::class);

    foreach ($ints as $int) {
      if (
        $int->getAttribute('kind') === PhpParser\Node\Scalar\Int_::KIND_OCT &&
        preg_match('/^0o\d+$/', $int->getAttribute('rawValue'))) {

        $this->raiseLintAtNode(
          $int,
          pht(
            'Octal notation prefixes are not available before PHP 8.1.'));
      }
    }

    $call_likes = $ast->findNodesOfKind(PhpParser\Node\Expr\CallLike::class);

    foreach ($call_likes as $call_like) {
      if ($call_like->isFirstClassCallable()) {
        $this->raiseLintAtNode(
          $call_like,
          pht(
            'First class callables are not available before PHP 8.1.'));
      }
    }

    $enums = $ast->findNodesOfKind(PhpParser\Node\Stmt\Enum_::class);

    foreach ($enums as $enum) {
      $this->raiseLintAtNode(
        $enum,
        pht(
          'Enums are not available before PHP 8.1.'));
    }

    $parameters = $ast->findNodesOfKind(PhpParser\Node\Param::class);

    foreach ($parameters as $parameter) {
      if ($parameter->type instanceof PhpParser\Node\IntersectionType) {
        $this->raiseLintAtNode(
          $parameter,
          pht('Intersection types are not available before PHP 8.1.'));
      }
    }

    $function_likes = $ast->findNodesOfKind(PhpParser\Node\FunctionLike::class);

    foreach ($function_likes as $function_like) {
      $return_type = $function_like->getReturnType();

      if (
        $return_type instanceof PhpParser\Node\Identifier &&
        $return_type->toLowerString() === 'never') {
        $this->raiseLintAtNode(
          $return_type,
          pht(
            'The `%s` type hint is not available before PHP 8.1.',
            'never'));
      }
    }

    $properties = $ast->findNodesOfKind(PhpParser\Node\Stmt\Property::class);

    foreach ($properties as $property) {
      if ($property->type instanceof PhpParser\Node\IntersectionType) {
        $this->raiseLintAtNode(
          $property->type,
          pht('Intersection types are not available before PHP 8.1.'));
      } else if ($property->isReadonly()) {
        $this->raiseLintAtNode(
          $property,
          pht(
            'Readonly properties are not available before PHP 8.1.'));
      }
    }

    $class_consts = $ast->findNodesOfKind(
      PhpParser\Node\Stmt\ClassConst::class);

    foreach ($class_consts as $class_const) {
      if ($class_const->isFinal()) {
        $this->raiseLintAtNode(
          $class_const,
          pht(
            'Class constants cannot be marked as final before PHP 8.1.'));
      }
    }
  }

  private function lintPHP81Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {

    $function_likes = $ast->findNodesOfKind(PhpParser\Node\FunctionLike::class);

    foreach ($function_likes as $function_like) {
      $previous = null;

      foreach ($function_like->getParams() as $parameter) {
        if (!$previous || !$previous->default) {
          $previous = $parameter;
          continue;
        }

        if (
          $previous->default instanceof PhpParser\Node\Expr\ConstFetch &&
          $previous->default->name->toLowerString() === 'null') {

          // The default value of null is an exception to the rule to retain
          // backwards compatibility with PHP 7.0 and older.
          // These parameters are still considered required, but do not throw
          // an exception when called.
          continue;
        }

        if (!$parameter->default) {
          $this->raiseLintAtNode(
            $previous,
            pht(
              'Optional parameters specified before a required parameter '.
              'are always considered required in PHP 8.1.'));
        }

        $previous = $parameter;
      }
    }
  }

  private function lintPHP82Features(PhpParserAst $ast, array $token_stream) {
    $classes = $ast->findNodesOfKind(PhpParser\Node\Stmt\Class_::class);

    foreach ($classes as $class) {
      if ($class->isReadonly() && !$class->isAnonymous()) {
        $this->raiseLintAtNode(
          $class,
          pht(
            'Readonly classes are not available before PHP 8.2.'));
      }
    }

    $traits = $ast->findNodesOfKind(PhpParser\Node\Stmt\Trait_::class);

    foreach ($traits as $trait) {
      foreach ($trait->getConstants() as $constant) {
        $this->raiseLintAtNode(
          $constant,
          pht(
            'Constants on traits are not available before PHP 8.2.'));
      }
    }

    $parameters = $ast->findNodesOfKind(PhpParser\Node\Param::class);

    foreach ($parameters as $parameter) {
      if ($parameter->type instanceof PhpParser\Node\Identifier) {
        $type = $parameter->type->toLowerString();
        if ($type === 'null' || $type === 'false') {
           $this->raiseLintAtNode(
             $parameter,
             pht(
               'Using `%s` as the only type hint is not allowed before '.
               'PHP 8.2.',
               $type));
        } else if ($type === 'true') {
           $this->raiseLintAtNode(
             $parameter,
             pht(
               'Using `%s` as a type hint is not allowed before PHP 8.2.',
               $type));
        }
      } else if ($parameter->type instanceof PhpParser\Node\UnionType) {
        foreach ($parameter->type->types as $sub_type) {
          if ($sub_type instanceof PhpParser\Node\IntersectionType) {
            $this->raiseLintAtNode(
              $parameter,
              pht(
                'Combining union and intersection types is not '.
                'supported before PHP 8.2.'));
            break;
          } else if (
            $sub_type instanceof PhpParser\Node\Identifier &&
            $sub_type->toLowerString() === 'true') {

            $this->raiseLintAtNode(
             $sub_type,
             pht(
               'Using `%s` as a type hint is not allowed before PHP 8.2.',
               $sub_type));
          }
        }
      }
    }

    $function_likes = $ast->findNodesOfKind(
      PhpParser\Node\FunctionLike::class);

    foreach ($function_likes as $function_like) {
      $return_type = $function_like->getReturnType();

      if (
        $return_type instanceof PhpParser\Node\Name &&
        $return_type->isSpecialClassName() &&
        $return_type->toLowerString() === 'static') {

        $this->raiseLintAtNode(
           $return_type,
           pht(
             'Using `%s` as a type hint is not allowed before PHP 8.2.',
             'static'));
      } else if ($return_type instanceof PhpParser\Node\Identifier) {
        $type = $return_type->toLowerString();
        if ($type === 'null' || $type === 'false') {
           $this->raiseLintAtNode(
             $return_type,
             pht(
               'Using `%s` as the only type hint is not allowed before '.
               'PHP 8.2.',
               $type));
        } else if ($type === 'true') {
           $this->raiseLintAtNode(
             $return_type,
             pht(
               'Using `%s` as a type hint is not allowed before PHP 8.2.',
               $type));
        }
      } else if ($return_type instanceof PhpParser\Node\UnionType) {
        foreach ($return_type->types as $sub_type) {
          if ($sub_type instanceof PhpParser\Node\IntersectionType) {
            $this->raiseLintAtNode(
              $return_type,
              pht(
                'Combining union and intersection types is not '.
                'supported before PHP 8.2.'));
            break;
          } else if (
            $sub_type instanceof PhpParser\Node\Name &&
            $sub_type->isSpecialClassName() &&
            $sub_type->toLowerString() === 'static') {

            $this->raiseLintAtNode(
              $sub_type,
              pht(
                'Using `%s` as a type hint is not allowed before PHP 8.2.',
                'static'));
          } else if (
            $sub_type instanceof PhpParser\Node\Identifier &&
            $sub_type->toLowerString() === 'true') {

            $this->raiseLintAtNode(
              $sub_type,
              pht(
                'Using `%s` as a type hint is not allowed before PHP 8.2.',
                $sub_type->toLowerString()));
          }
        }
      }
    }

    $properties = $ast->findNodesOfKind(PhpParser\Node\Stmt\Property::class);

    foreach ($properties as $property) {
      if ($property->type instanceof PhpParser\Node\Identifier) {
        $type = $property->type->toLowerString();
        if ($type === 'null' || $type === 'false') {
           $this->raiseLintAtNode(
             $property,
             pht(
               'Using `%s` as the only type hint is not allowed before '.
               'PHP 8.2.',
               $type));
        } else if ($type === 'true') {
           $this->raiseLintAtNode(
             $property,
             pht(
               'Using `%s` as a type hint is not allowed before PHP 8.2.',
               $type));
        }
      } else if ($property->type instanceof PhpParser\Node\UnionType) {
        foreach ($property->type->types as $type) {
          if ($type instanceof PhpParser\Node\IntersectionType) {
            $this->raiseLintAtNode(
              $property,
              pht(
                'Combining union and intersection types is not '.
                'supported before PHP 8.2.'));
            break;
          } else if (
            $type instanceof PhpParser\Node\Identifier &&
            $type->toLowerString() === 'true') {

            $this->raiseLintAtNode(
             $type,
             pht(
               'Using `%s` as a type hint is not allowed before PHP 8.2.',
               $type));
          }
        }
      }
    }
  }

  private function lintPHP82Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {}

  private function lintPHP83Features(PhpParserAst $ast, array $token_stream) {
     $classes = $ast->findNodesOfKind(PhpParser\Node\Stmt\Class_::class);

    foreach ($classes as $class) {
      if ($class->isReadonly() && $class->isAnonymous()) {
        $this->raiseLintAtNode(
          $class,
          pht(
            'Anonymous readonly classes are not available before PHP 8.3.'));
      }
    }

    $class_likes = $ast->findNodesOfKind(
      PhpParser\Node\Stmt\ClassLike::class);

    foreach ($class_likes as $class_like) {
      foreach ($class_like->getConstants() as $constant) {
        if ($constant->type) {
          $this->raiseLintAtNode(
            $constant->type,
            pht(
              'Typed constants are not available before PHP 8.3.'));
        }
      }
    }

    $static_variables = $ast->findNodesOfKind(PhpParser\Node\StaticVar::class);

    foreach ($static_variables as $static_variable) {
      if (!$static_variable->default) {
        continue;
      }

      if ($static_variable->default instanceof PhpParser\Node\Expr\CallLike) {
        $this->raiseLintAtNode(
          $static_variable->default,
          pht(
            'Dynamic expressions cannot be used as initializers for '.
             'static variables before PHP 8.3.'));
      }
    }
  }

  private function lintPHP83Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {}

  private function lintPHP84Features(PhpParserAst $ast, array $token_stream) {

    $properties = $ast->findNodesOfKind(PhpParser\Node\Stmt\Property::class);

    foreach ($properties as $property) {
      if ($property->hooks) {
        $this->raiseLintAtNode(
          $property,
          pht('Property hooks are not available before PHP 8.4.'));
      }

      if (
        !$property->isStatic() &&
        ($property->isPrivateSet() ||
         $property->isProtectedSet() ||
         $property->isPublicSet())) {

        $this->raiseLintAtNode(
          $property,
          pht(
            'Asymetric property visibility is not available before '.
            'PHP 8.4.'));
      }
    }

    $parameters = $ast->findNodesOfKind(PhpParser\Node\Param::class);
    foreach ($parameters as $parameter) {
      if ($parameter->hooks) {
        $this->raiseLintAtNode(
          $parameter,
          pht('Property hooks are not available before PHP 8.4.'));
      }
    }

    $member_accesses = $ast->findNodesOfKinds(
      array(
        PhpParser\Node\Expr\MethodCall::class,
        PhpParser\Node\Expr\NullsafeMethodCall::class,
        PhpParser\Node\Expr\PropertyFetch::class,
        PhpParser\Node\Expr\NullsafePropertyFetch::class,
      ));

    foreach ($member_accesses as $member_access) {
      $var = $member_access->var;

      if (
        $var instanceof PhpParser\Node\Expr\New_ &&
        $var->getStartTokenPos() === $member_access->getStartTokenPos()) {
        $this->raiseLintAtNode(
          $member_access,
          pht(
            'Class member access on instantiation without '.
            'parentheses was not introduced until PHP 8.4, but this '.
            'codebase targets an earlier version of PHP.'));
      }
    }
  }

  private function lintPHP84Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {}

  private function lintPHP85Features(PhpParserAst $ast, array $token_stream) {
    $void_casts = $ast->findNodesOfKind(PhpParser\Node\Expr\Cast\Void_::class);

    foreach ($void_casts as $void_cast) {
      $this->raiseLintAtNode(
        $void_cast,
        pht(
          'Void casts (`%s`) are not available before PHP 8.4.',
          '(void)'));
    }

    $properties = $ast->findNodesOfKind(PhpParser\Node\Stmt\Property::class);

    foreach ($properties as $property) {
      if (
        $property->isStatic() &&
        ($property->isPrivateSet() ||
         $property->isProtectedSet() ||
         $property->isPublicSet())) {

        $this->raiseLintAtNode(
          $property,
          pht(
            'Asymetric property visibility for static properties is '.
            'not available before PHP 8.5.'));
      }
    }

    // This deliberately doesn't fetch children to ensure continual use of the
    // pipe operator only results in one lint error, not one for every
    // encountered operator in the pipe.
    $pipes = $ast->findTopLevelNodesOfKind(
      PhpParser\Node\Expr\BinaryOp\Pipe::class);

    foreach ($pipes as $pipe) {
      $this->raiseLintAtNode(
        $pipe,
        pht('The pipe operator is not available before PHP 8.5.'));
    }

    $parameters = $ast->findNodesOfKind(PhpParser\Node\Param::class);

    foreach ($parameters as $parameter) {
      if ($parameter->flags & PhpParser\Modifiers::FINAL) {
        $this->raiseLintAtNode(
          $parameter,
          pht('Final property promotion is not available before PHP 8.5.'));
      }
    }
  }

  private function lintPHP85Incompatibilities(
    PhpParserAst $ast,
    array $token_stream) {}

  /**
   * @param array $compat_info
   * @param array $whitelist
   * @param iterable<PhpParser\Node\Name> $nodes
   * @param string $type
   */
  private function lintTypeCompatibility(
    array $compat_info,
    array $whitelist,
    iterable $nodes,
    string $type) {

    foreach ($nodes as $node) {
      $name = $node->toString();
      $version = idx($compat_info, $name, array());
      $min = idx($version, 'php.min');
      $max = idx($version, 'php.max');

      $whitelisted = false;
      foreach (idx($whitelist[$type], $name, array()) as $range) {
        $node_token_range = range(
          $node->getStartTokenPos(),
          $node->getEndTokenPos());

        if (array_intersect($range, $node_token_range)) {
          $whitelisted = true;
          break;
        }
      }

      if ($whitelisted) {
        continue;
      }

      if ($min && version_compare($min, $this->version, '>')) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'This codebase targets PHP %s, but `%s` was not '.
            'introduced until PHP %s.',
            $this->version,
            $name,
            $min));
      } else if ($max && version_compare($max, $this->version, '<')) {
        $this->raiseLintAtNode(
          $node,
          pht(
            'This codebase targets PHP %s, but `%s` was '.
            'removed in PHP %s.',
            $this->version,
            $name,
            $max));
      }
    }
  }

  private function lintConstantCompatibility(
    string $name,
    PhpParser\Node $node,
    array $whitelist,
    array $compat_info) {

    $version = idx($compat_info['constants'], $name, array());
    $min = idx($version, 'php.min');
    $max = idx($version, 'php.max');

    $whitelisted = false;
    foreach (idx($whitelist['constant'], $name, array()) as $range) {
      $const_token_range = range(
        $node->getStartTokenPos(),
        $node->getEndTokenPos());

      if (array_intersect($range, $const_token_range)) {
        $whitelisted = true;
        break;
      }
    }

    if ($whitelisted) {
      return;
    }

    if ($min && version_compare($min, $this->version, '>')) {
      $this->raiseLintAtNode(
        $node,
        pht(
          'This codebase targets PHP %s, but `%s` was not '.
          'introduced until PHP %s.',
          $this->version,
          $name,
          $min));
    } else if ($max && version_compare($max, $this->version, '<')) {
      $this->raiseLintAtNode(
        $node,
        pht(
          'This codebase targets PHP %s, but `%s` was '.
          'removed in PHP %s.',
          $this->version,
          $name,
          $max));
    }
  }

}
