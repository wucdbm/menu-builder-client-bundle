# menu-builder-client-bundle

A client for the Menu Builder Bundle

- UX: We know the route chosen, so we know the controller and the action. Then, we could inspect the action for parameter types. This poses the threat of "Object not found" Exceptions when an entity does not exist, so we have to also read the docblock with the new symfony reader and determine the type. If it is an entity, read entities and return a list. This could potentially lead to shittons of entities fetched, so when fetching the entities, if the repository provided implements SomeRandomMenuBuilderInterface which has one method: getMenuBuilderQueryBuilder, return that, otherwise $repository->createQueryBuilder(); Also, use __toString()