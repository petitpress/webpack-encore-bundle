<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Latte\Nodes;

use Generator;
use Latte\Compiler\Tag;
use Latte\CompileException;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\Nodes\Php\ModifierNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;

class EncoreJs extends StatementNode
{
	public ExpressionNode $file;
	public ArrayNode $args;
	public ModifierNode $modifier;

	/**
	 * @throws CompileException
	 */
	public static function create(Tag $tag): static
	{
		$tag->expectArguments();
		$node = new static;
		$node->file = $tag->parser->parseUnquotedStringOrExpression();

		$tag->parser->stream->tryConsume(',');
		$node->args = $tag->parser->parseArguments();
		$node->modifier = $tag->parser->parseModifier();

		return $node;
	}

	public function print(PrintContext $context): string
	{
		return $context->format(
			'echo %modify($this->global->webpackEncoreTagRenderer->renderJsTags(%node, %args)) %line;',
			$this->modifier,
			$this->file,
			$this->args,
			$this->position,
		);
	}

	public function &getIterator(): Generator
	{
		yield $this->file;
		yield $this->args;
		yield $this->modifier;
	}
}
