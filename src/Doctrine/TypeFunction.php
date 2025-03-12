<?php
	namespace DaybreakStudios\DoctrineQueryDocument\Doctrine;

	use Doctrine\ORM\Mapping\ClassMetadataInfo;
	use Doctrine\ORM\Query\AST\Functions\FunctionNode;
	use Doctrine\ORM\Query\Lexer;
	use Doctrine\ORM\Query\Parser;
	use Doctrine\ORM\Query\QueryException;
	use Doctrine\ORM\Query\SqlWalker;

	class TypeFunction extends FunctionNode {
		/**
		 * @var string
		 */
		public string $dqlAlias;

		public function getSql(SqlWalker $sqlWalker): string {
			$component = $sqlWalker->getQueryComponent($this->dqlAlias);

			/** @var ClassMetadataInfo $metadata */
			$metadata = $component['metadata'];
			$alias = $sqlWalker->getSQLTableAlias($metadata->getTableName(), $this->dqlAlias);

			if (!isset($metadata->discriminatorColumn['name']))
				throw QueryException::semanticalError('TYPE() only supports entities with a discriminator column.');

			return sprintf('%s.%s', $alias, $metadata->discriminatorColumn['name']);
		}

		public function parse(Parser $parser): void {
			$parser->match(Lexer::T_IDENTIFIER);
			$parser->match(Lexer::T_OPEN_PARENTHESIS);
			$this->dqlAlias = $parser->IdentificationVariable();
			$parser->match(Lexer::T_CLOSE_PARENTHESIS);
		}
	}