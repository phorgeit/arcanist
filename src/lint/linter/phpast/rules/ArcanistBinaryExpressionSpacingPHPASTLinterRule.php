<?php

/**
 * @phutil-external-symbol class PhpParser\Node
 * @phutil-external-symbol class PhpParser\Node\Const_
 * @phutil-external-symbol class PhpParser\Node\Expr\ArrayItem
 * @phutil-external-symbol class PhpParser\Node\Expr\Assign
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignOp
 * @phutil-external-symbol class PhpParser\Node\Expr\AssignRef
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\Concat
 * @phutil-external-symbol class PhpParser\Node\Expr\BinaryOp\NotEqual
 * @phutil-external-symbol class PhpParser\Node\Expr\Instanceof_
 * @phutil-external-symbol class PhpParser\Node\Param
 * @phutil-external-symbol class PhpParser\Node\PropertyItem
 */
final class ArcanistBinaryExpressionSpacingPHPASTLinterRule
  extends ArcanistPHPASTNodeLinterRule {

  const ID = 27;

  public function getLintName() {
    return pht('Space Around Binary Operator');
  }

  public function getLintSeverity() {
    return ArcanistLintSeverity::SEVERITY_WARNING;
  }

  public function process(PhpParser\Node $node, array $token_stream) {
    if ($node instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
      // The concatenation operator does not have spaces around it.
      return;
    } else if ($node instanceof PhpParser\Node\Param) {
      if (!$node->default) {
        return;
      }

      $sigils = array('=');
      $left = $node->var;
      $right = $node->default;
    } else if ($node instanceof PhpParser\Node\PropertyItem) {
      if (!$node->default) {
        return;
      }

      $left = $node->name;
      $right = $node->default;
      $sigils = array('=');
    } else if ($node instanceof PhpParser\Node\Const_) {
      $left = $node->name;
      $right = $node->value;
      $sigils = array('=');
    } else if ($node instanceof PhpParser\Node\Expr\BinaryOp) {
      $left = $node->left;
      $right = $node->right;
      $sigils = array($node->getOperatorSigil());

      // Not equal is unique in that it supports two notations:
      // != and <>.
      if ($node instanceof PhpParser\Node\Expr\BinaryOp\NotEqual) {
        $sigils[] = '<>';
      }
    } else if (
      $node instanceof PhpParser\Node\Expr\Assign ||
      $node instanceof PhpParser\Node\Expr\AssignOp ||
      $node instanceof PhpParser\Node\Expr\AssignRef) {
      $left = $node->var;
      $right = $node->expr;

      switch ($node->getType()) {
        case 'Expr_Assign':
        case 'Expr_AssignRef':
          $sigils = array('=');
          break;
        case 'Expr_AssignOp_BitwiseAnd':
          $sigils = array('&=');
          break;
        case 'Expr_AssignOp_BitwiseOr':
          $sigils = array('|=');
          break;
        case 'Expr_AssignOp_BitwiseXor':
          $sigils = array('^=');
          break;
        case 'Expr_AssignOp_Coalesce':
          $sigils = array('??=');
          break;
        case 'Expr_AssignOp_Concat':
          $sigils = array('.=');
          break;
        case 'Expr_AssignOp_Div':
          $sigils = array('/=');
          break;
        case 'Expr_AssignOp_Minus':
          $sigils = array('-=');
          break;
        case 'Expr_AssignOp_Mod':
          $sigils = array('%=');
          break;
        case 'Expr_AssignOp_Mul':
          $sigils = array('*=');
          break;
        case 'Expr_AssignOp_Plus':
          $sigils = array('+=');
          break;
        case 'Expr_AssignOp_Pow':
          $sigils = array('**=');
          break;
        case 'Expr_AssignOp_ShiftLeft':
          $sigils = array('<<=');
          break;
        case 'Expr_AssignOp_ShiftRight':
          $sigils = array('>>=');
          break;
        default:
          return;
      }
    } else if ($node instanceof PhpParser\Node\Expr\ArrayItem && $node->key) {
      $left = $node->key;
      $right = $node->value;
      $sigils = array('=>');
    } else if ($node instanceof PhpParser\Node\Expr\Instanceof_) {
      $left = $node->expr;
      $right = $node->class;
      $sigils = array('instanceof');
    } else {
      return;
    }

    $op_tokens = array_slice(
      $token_stream,
      $left->getEndTokenPos(),
      $right->getStartTokenPos() - $left->getEndTokenPos(),
      true);

    $op_token = null;
    $op_token_index = -1;

    foreach ($op_tokens as $i => $token) {
      if (in_array($token->text, $sigils, true)) {
        $op_token = $token;
        $op_token_index = $i;
        break;
      }
    }

    if (!$op_token) {
      return;
    }

    $before = last(
      $this->getNonsemanticTokensBefore($op_token_index, $token_stream));
    $after = head(
      $this->getNonsemanticTokensAfter($op_token_index, $token_stream));

    $replace = null;
    if (
      (!$before || !$before->is(T_WHITESPACE)) &&
      (!$after || !$after->is(T_WHITESPACE))) {

      $replace = " {$op_token->text} ";
    } else if (!$before || !$before->is(T_WHITESPACE)) {
      $replace = " {$op_token->text}";
    } else if (!$after || !$after->is(T_WHITESPACE)) {
      $replace = "{$op_token->text} ";
    }

    if ($replace !== null) {
      if (in_array('=>', $sigils, true)) {
        $this->raiseLintAtToken(
          $op_token,
          pht('Convention: double arrow should be surrounded by whitespace.'),
          $replace);
      } else {
        $this->raiseLintAtToken(
          $op_token,
          pht(
            'Convention: logical and arithmetic operators should be '.
            'surrounded by whitespace.'),
          $replace);
      }
    }
  }

}
