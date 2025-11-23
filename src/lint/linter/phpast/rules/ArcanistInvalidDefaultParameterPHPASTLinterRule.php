<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Expr
 * @phutil-external-symbol class PhpParser\Node\Name
 * @phutil-external-symbol class PhpParser\Node\Param
 * @phutil-external-symbol class PhpParser\Node\Expr\ConstFetch
 * @phutil-external-symbol class PhpParser\Node\NullableType
 * @phutil-external-symbol class PhpParser\Node\Identifier
 * @phutil-external-symbol class PhpParser\Node\UnionType
 * @phutil-external-symbol class PhpParser\Node\IntersectionType
 * @phutil-external-symbol class PhpParser\Node\Expr\Array_
 * @phutil-external-symbol class PhpParser\Node\Scalar\Int_
 * @phutil-external-symbol class PhpParser\Node\Scalar\Float_
 * @phutil-external-symbol class PhpParser\Node\Scalar\String_
 * @phutil-external-symbol class PhpParser\Node\Expr\New_
 * @phutil-external-symbol class PhpParser\Node\Param
 * @phutil-external-symbol class PhpParser\Node\Param
 */
final class ArcanistInvalidDefaultParameterPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 70;

  public function getLintName() {
    return pht('Invalid Default Parameter');
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($this->version) {
      $target_version = $this->version;
    } else {
      $target_version = PHP_VERSION;
    }

    // PHP 7.1.0 introduced nullable types.
    // Prior to that version, using a default value of null was the only
    // way to mark a typed parameter as nullable.
    $supports_nullable_types = version_compare($target_version, '7.1.0', '>=');

    if ($node instanceof PhpParser\Node\Param) {
      if (!$node->type || !$node->default) {
        return;
      }

      if (
        $node->default instanceof PhpParser\Node\Expr\ConstFetch &&
        $node->default->name->toLowerString() === 'null') {

        if (
          $node->type instanceof PhpParser\Node\NullableType ||
          ($node->type instanceof PhpParser\Node\Identifier &&
           $node->type->toLowerString() === 'null')) {
          return;
        } else if ($node->type instanceof PhpParser\Node\UnionType) {
          foreach ($node->type->types as $type) {
            if (
              $type instanceof PhpParser\Node\Identifier &&
              $type->toLowerString() === 'null') {

              return;
            }
          }

          $replacement = $this->getSemanticString($node->type, $token_stream).
            '|null';
        } else if (!$supports_nullable_types) {
          return;
        } else if ($node->type instanceof PhpParser\Node\IntersectionType) {
          $replacement = '('.
            $this->getString($node->type, $token_stream).')|null';
        } else {
          $replacement = '?'.$node->type->toString();
        }

        $this->raiseLintAtNode(
          $node->type,
          pht(
            'Implicitly marking parameters as nullable is deprecated '.
            'beginning with PHP 8.4.0.'),
          $replacement,
          $token_stream);
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toLowerString() === 'array') {

        if (!($node->default instanceof PhpParser\Node\Expr\Array_)) {
          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with an array type hint '.
              'can only be an array.'));
        }
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toLowerString() === 'bool') {

        if (
          !($node->default instanceof PhpParser\Node\Expr\ConstFetch) ||
          ($node->default->name->toLowerString() !== 'false' &&
           $node->default->name->toLowerString() !== 'true')) {

          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with a bool type hint '.
              'can only be true or false.'));
        }
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toLowerString() === 'true') {

        if (
          !($node->default instanceof PhpParser\Node\Expr\ConstFetch) ||
          $node->default->name->toLowerString() !== 'true') {

          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with a true type hint '.
              'can only be true.'));
        }
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toLowerString() === 'false') {

        if (
          !($node->default instanceof PhpParser\Node\Expr\ConstFetch) ||
          $node->default->name->toLowerString() !== 'false') {

          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with a false type hint '.
              'can only be false.'));
        }
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toString() === 'int') {

        if (!($node->default instanceof PhpParser\Node\Scalar\Int_)) {
          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with an int type hint '.
              'can only be an integer.'));
        }
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toString() === 'float') {

        if (
          !($node->default instanceof PhpParser\Node\Scalar\Int_) &&
          !($node->default instanceof PhpParser\Node\Scalar\Float_)) {
          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with a float type hint '.
              'can only be an integer or a floating point number.'));
        }
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toLowerString() === 'string') {

        if (!($node->default instanceof PhpParser\Node\Scalar\String_)) {
          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with a string type hint '.
              'can only be a string.'));
        }
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toLowerString() === 'callable') {

        if (
          !($node->default instanceof PhpParser\Node\Scalar\String_) &&
          !($node->default instanceof PhpParser\Node\Expr\Array_) &&
          !($node->default instanceof PhpParser\Node\Expr\New_)) {

          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with a callable type hint '.
              'can only be a string, an array or an instantiation of a type '.
              'declaring an `%s` method.',
              '__invoke'));
        }
      } else if ($node->type instanceof PhpParser\Node\Name) {
        if (!($node->default instanceof PhpParser\Node\Expr\New_)) {
          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with a class type hint '.
              'can only be an instantiation of that class or a descendant.'));
        }
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toLowerString() === 'iterable') {

        if (
          !($node->default instanceof PhpParser\Node\Expr\Array_) &&
          !($node->default instanceof PhpParser\Node\Expr\New_)) {

          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with an iterable type hint '.
              'can only be an instantation of an iterable type or an array.'));
        }
      } else if (
        $node->type instanceof PhpParser\Node\Identifier &&
        $node->type->toLowerString() === 'object') {

        if (!($node->default instanceof PhpParser\Node\Expr\New_)) {
          $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with an object type hint '.
              'can only be an instantation of an object.'));
        }
      } else if ($node->type instanceof PhpParser\Node\UnionType) {
        $valid_default = $this->checkUnionTypeDefaultParameter(
          $node->type->types,
          $node->default);

        if (!$valid_default) {
           $this->raiseLintAtNode(
            $node->default,
            pht(
              'Default value for parameters with a union type hint '.
              'can only be a value of one of its types.'));
        }
      }
    }
  }

  private function checkUnionTypeDefaultParameter(
    array $types,
    PhpParser\Node\Expr $default) {

    foreach ($types as $type) {
      if ($type instanceof PhpParser\Node\Identifier) {
        switch ($type->toLowerString()) {
          case 'true':
            if (
              ($default instanceof PhpParser\Node\Expr\ConstFetch) &&
              $default->name->toLowerString() === 'true') {
              return true;
            }
            break;
          case 'false':
            if (
              ($default instanceof PhpParser\Node\Expr\ConstFetch) &&
              $default->name->toLowerString() === 'false') {
              return true;
            }
            break;
          case 'bool':
            if (
              ($default instanceof PhpParser\Node\Expr\ConstFetch) &&
              ($default->name->toLowerString() === 'true' ||
               $default->name->toLowerString() === 'false')) {
              return true;
            }
            break;
          case 'string':
            if ($default instanceof PhpParser\Node\Scalar\String_) {
              return true;
            }
            break;
          case 'array':
            if ($default instanceof PhpParser\Node\Expr\Array_) {
              return true;
            }
            break;
          case 'callable':
            if (
              $default instanceof PhpParser\Node\Expr\Array_ ||
              $default instanceof PhpParser\Node\Scalar\String_ ||
              $default instanceof PhpParser\Node\Expr\New_) {
              return true;
            }
            break;
          case 'iterable':
            if (
              $default instanceof PhpParser\Node\Expr\Array_ ||
              $default instanceof PhpParser\Node\Expr\New_) {
              return true;
            }
            break;
          case 'object':
            if ($default instanceof PhpParser\Node\Expr\New_) {
              return true;
            }
            break;
          case 'int':
            if ($default instanceof PhpParser\Node\Scalar\Int_) {
              return true;
            }
            break;
          case 'float':
            if (
              $default instanceof PhpParser\Node\Scalar\Int_ ||
              $default instanceof PhpParser\Node\Scalar\Float_) {
              return true;
            }
            break;
          default:
            // Assume other types are valid by default to be forward compatible.
            return true;
        }
      } else if ($type instanceof PhpParser\Node\Name) {
        if ($default instanceof PhpParser\Node\Expr\New_) {
          return true;
        }
      }
    }

    return false;
  }

}
