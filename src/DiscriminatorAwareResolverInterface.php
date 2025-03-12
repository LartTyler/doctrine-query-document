<?php
	namespace DaybreakStudios\DoctrineQueryDocument;

	interface DiscriminatorAwareResolverInterface {
		public function setDiscriminatorResolverFunction(?string $name): void;
	}