<?php

declare(strict_types=1);

namespace Tests\ORM;

use Aphrodite\ORM\Attributes\BelongsTo as BelongsToAttribute;
use Aphrodite\ORM\Attributes\BelongsToMany as BelongsToManyAttribute;
use Aphrodite\ORM\Attributes\HasMany as HasManyAttribute;
use Aphrodite\ORM\Attributes\HasOne as HasOneAttribute;
use Aphrodite\ORM\Attributes\ManyToMany;
use Aphrodite\ORM\Attributes\ManyToOne;
use Aphrodite\ORM\Entity;
use Aphrodite\ORM\Relations\BelongsToMany;
use Aphrodite\ORM\Relations\BelongsTo as BelongsToRelation;
use Aphrodite\ORM\Relations\HasMany as HasManyRelation;
use Aphrodite\ORM\Relations\HasOne as HasOneRelation;
use Aphrodite\ORM\Relations\LoadsRelations;
use Aphrodite\ORM\Relations\Relation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test entities for relation tests.
 */
class TestUser extends Entity
{
    use LoadsRelations;

    public static function getTable(): string
    {
        return 'users';
    }

    #[HasManyAttribute(targetEntity: TestPost::class, foreignKey: 'user_id')]
    public ?array $posts = null;

    #[HasOneAttribute(targetEntity: TestProfile::class, foreignKey: 'user_id')]
    public ?TestProfile $profile = null;

    #[BelongsToManyAttribute(targetEntity: TestRole::class)]
    public ?array $roles = null;
}

class TestPost extends Entity
{
    use LoadsRelations;

    public static function getTable(): string
    {
        return 'posts';
    }

    #[BelongsToAttribute(targetEntity: TestUser::class, foreignKey: 'user_id')]
    public ?TestUser $user = null;

    #[HasManyAttribute(targetEntity: TestComment::class, foreignKey: 'post_id')]
    public ?array $comments = null;
}

class TestProfile extends Entity
{
    use LoadsRelations;

    public static function getTable(): string
    {
        return 'profiles';
    }
}

class TestComment extends Entity
{
    use LoadsRelations;

    public static function getTable(): string
    {
        return 'comments';
    }
}

class TestRole extends Entity
{
    use LoadsRelations;

    public static function getTable(): string
    {
        return 'roles';
    }
}

/**
 * Tests for ORM relations.
 */
class RelationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Entity::useMemoryStore(true);
        Entity::reset();
    }

    protected function tearDown(): void
    {
        Entity::reset();
        BelongsToMany::resetPivotStore();
        parent::tearDown();
    }

    #[Test]
    public function relation_base_class_can_be_instantiated(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new HasManyRelation($user, TestPost::class, 'user_id', 'id');

        $this->assertInstanceOf(Relation::class, $relation);
        $this->assertEquals(TestPost::class, $relation->getRelated());
        $this->assertSame($user, $relation->getParent());
        $this->assertFalse($relation->isLoaded());
    }

    #[Test]
    public function has_many_returns_empty_array_when_no_related(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new HasManyRelation($user, TestPost::class, 'user_id', 'id');

        $result = $relation->get();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertTrue($relation->isLoaded());
    }

    #[Test]
    public function has_many_returns_related_entities(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $post1 = TestPost::create(['id' => 1, 'title' => 'First Post', 'user_id' => 1]);
        $post2 = TestPost::create(['id' => 2, 'title' => 'Second Post', 'user_id' => 1]);
        $otherPost = TestPost::create(['id' => 3, 'title' => 'Other Post', 'user_id' => 2]);

        $relation = new HasManyRelation($user, TestPost::class, 'user_id', 'id');
        $result = $relation->get();

        $this->assertCount(2, $result);
        $this->assertTrue($relation->isLoaded());
    }

    #[Test]
    public function has_many_caches_result(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new HasManyRelation($user, TestPost::class, 'user_id', 'id');

        $result1 = $relation->get();
        $result2 = $relation->get();

        $this->assertSame($result1, $result2);
    }

    #[Test]
    public function has_many_create_creates_related_entity(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new HasManyRelation($user, TestPost::class, 'user_id', 'id');

        $post = $relation->create(['title' => 'New Post', 'content' => 'Content']);

        $this->assertInstanceOf(TestPost::class, $post);
        $this->assertEquals(1, $post->user_id);
        $this->assertEquals('New Post', $post->title);
    }

    #[Test]
    public function has_many_count_returns_correct_count(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        TestPost::create(['id' => 1, 'title' => 'Post 1', 'user_id' => 1]);
        TestPost::create(['id' => 2, 'title' => 'Post 2', 'user_id' => 1]);
        TestPost::create(['id' => 3, 'title' => 'Post 3', 'user_id' => 2]);

        $relation = new HasManyRelation($user, TestPost::class, 'user_id', 'id');

        $this->assertEquals(2, $relation->count());
    }

    #[Test]
    public function has_many_exists_returns_true_when_has_related(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        TestPost::create(['id' => 1, 'title' => 'Post', 'user_id' => 1]);

        $relation = new HasManyRelation($user, TestPost::class, 'user_id', 'id');

        $this->assertTrue($relation->exists());
    }

    #[Test]
    public function has_many_exists_returns_false_when_no_related(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new HasManyRelation($user, TestPost::class, 'user_id', 'id');

        $this->assertFalse($relation->exists());
    }

    #[Test]
    public function belongs_to_returns_null_when_no_parent(): void
    {
        $post = TestPost::create(['id' => 1, 'title' => 'Post', 'user_id' => null]);
        $relation = new BelongsToRelation($post, TestUser::class, 'user_id', 'id');

        $result = $relation->get();

        $this->assertNull($result);
        $this->assertTrue($relation->isLoaded());
    }

    #[Test]
    public function belongs_to_returns_parent_entity(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $post = TestPost::create(['id' => 1, 'title' => 'Post', 'user_id' => 1]);

        $relation = new BelongsToRelation($post, TestUser::class, 'user_id', 'id');
        $result = $relation->get();

        $this->assertInstanceOf(TestUser::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('John', $result->name);
    }

    #[Test]
    public function belongs_to_associate_sets_foreign_key(): void
    {
        $user = TestUser::create(['id' => 5, 'name' => 'John']);
        $post = TestPost::create(['id' => 1, 'title' => 'Post', 'user_id' => null]);

        $relation = new BelongsToRelation($post, TestUser::class, 'user_id', 'id');
        $relation->associate($user);

        $this->assertEquals(5, $post->user_id);
        $this->assertTrue($relation->isLoaded());
    }

    #[Test]
    public function belongs_to_dissociate_clears_foreign_key(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $post = TestPost::create(['id' => 1, 'title' => 'Post', 'user_id' => 1]);

        $relation = new BelongsToRelation($post, TestUser::class, 'user_id', 'id');
        $relation->dissociate();

        $this->assertNull($post->user_id);
    }

    #[Test]
    public function has_one_returns_null_when_no_related(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new HasOneRelation($user, TestProfile::class, 'user_id', 'id');

        $result = $relation->get();

        $this->assertNull($result);
        $this->assertTrue($relation->isLoaded());
    }

    #[Test]
    public function has_one_returns_related_entity(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        TestProfile::create(['id' => 1, 'bio' => 'Developer', 'user_id' => 1]);

        $relation = new HasOneRelation($user, TestProfile::class, 'user_id', 'id');
        $result = $relation->get();

        $this->assertInstanceOf(TestProfile::class, $result);
        $this->assertEquals(1, $result->user_id);
    }

    #[Test]
    public function has_one_create_creates_related_entity(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new HasOneRelation($user, TestProfile::class, 'user_id', 'id');

        $profile = $relation->create(['bio' => 'Developer']);

        $this->assertInstanceOf(TestProfile::class, $profile);
        $this->assertEquals(1, $profile->user_id);
    }

    #[Test]
    public function belongs_to_many_returns_empty_array_when_no_related(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new BelongsToMany($user, TestRole::class, 'role_user', 'user_id', 'role_id');

        $result = $relation->get();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertTrue($relation->isLoaded());
    }

    #[Test]
    public function belongs_to_many_guessed_pivot_table(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new BelongsToMany($user, TestRole::class);

        $this->assertEquals('roles_users', $relation->getPivotTable());
    }

    #[Test]
    public function belongs_to_many_count_returns_zero_when_no_related(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $relation = new BelongsToMany($user, TestRole::class, 'role_user', 'user_id', 'role_id');

        $this->assertEquals(0, $relation->count());
    }

    #[Test]
    public function loads_relations_trait_can_check_if_loaded(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);

        $this->assertFalse($user->relationLoaded('posts'));
    }

    #[Test]
    public function loads_relations_trait_can_load_relation(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        TestPost::create(['id' => 1, 'title' => 'Post', 'user_id' => 1]);

        $posts = $user->loadRelation('posts');

        $this->assertIsArray($posts);
        $this->assertCount(1, $posts);
        $this->assertTrue($user->relationLoaded('posts'));
    }

    #[Test]
    public function loads_relations_trait_can_load_multiple_relations(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        TestPost::create(['id' => 1, 'title' => 'Post', 'user_id' => 1]);
        TestProfile::create(['id' => 1, 'bio' => 'Dev', 'user_id' => 1]);

        $user->loadRelations(['posts', 'profile']);

        $this->assertTrue($user->relationLoaded('posts'));
        $this->assertTrue($user->relationLoaded('profile'));
    }

    #[Test]
    public function loads_relations_trait_returns_cached_relation(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        TestPost::create(['id' => 1, 'title' => 'Post', 'user_id' => 1]);

        $posts1 = $user->loadRelation('posts');
        $posts2 = $user->getRelation('posts');

        $this->assertSame($posts1, $posts2);
    }

    #[Test]
    public function belongs_to_attribute_has_correct_properties(): void
    {
        $attribute = new BelongsToAttribute(
            targetEntity: TestUser::class,
            foreignKey: 'user_id',
            ownerKey: 'id'
        );

        $this->assertEquals(TestUser::class, $attribute->targetEntity);
        $this->assertEquals('user_id', $attribute->foreignKey);
        $this->assertEquals('id', $attribute->ownerKey);
    }

    #[Test]
    public function has_many_attribute_has_correct_properties(): void
    {
        $attribute = new HasManyAttribute(
            targetEntity: TestPost::class,
            foreignKey: 'user_id',
            localKey: 'id'
        );

        $this->assertEquals(TestPost::class, $attribute->targetEntity);
        $this->assertEquals('user_id', $attribute->foreignKey);
        $this->assertEquals('id', $attribute->localKey);
    }

    #[Test]
    public function has_one_attribute_has_correct_properties(): void
    {
        $attribute = new HasOneAttribute(
            targetEntity: TestProfile::class,
            foreignKey: 'user_id',
            localKey: 'id'
        );

        $this->assertEquals(TestProfile::class, $attribute->targetEntity);
        $this->assertEquals('user_id', $attribute->foreignKey);
        $this->assertEquals('id', $attribute->localKey);
    }

    #[Test]
    public function belongs_to_many_attribute_has_correct_properties(): void
    {
        $attribute = new BelongsToManyAttribute(
            targetEntity: TestRole::class,
            pivotTable: 'role_user',
            pivotForeignKey: 'user_id',
            pivotRelatedKey: 'role_id'
        );

        $this->assertEquals(TestRole::class, $attribute->targetEntity);
        $this->assertEquals('role_user', $attribute->pivotTable);
        $this->assertEquals('user_id', $attribute->pivotForeignKey);
        $this->assertEquals('role_id', $attribute->pivotRelatedKey);
    }

    #[Test]
    public function many_to_one_attribute_exists(): void
    {
        $attribute = new ManyToOne(
            targetEntity: TestUser::class,
            inversedBy: 'posts'
        );

        $this->assertEquals(TestUser::class, $attribute->targetEntity);
        $this->assertEquals('posts', $attribute->inversedBy);
    }

    #[Test]
    public function many_to_many_attribute_exists(): void
    {
        $attribute = new ManyToMany(
            targetEntity: TestRole::class,
            mappedBy: 'users'
        );

        $this->assertEquals(TestRole::class, $attribute->targetEntity);
        $this->assertEquals('users', $attribute->mappedBy);
    }

    #[Test]
    public function belongs_to_many_attach_and_get(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $role = TestRole::create(['id' => 1, 'name' => 'Admin']);

        $relation = new BelongsToMany($user, TestRole::class, 'role_user', 'user_id', 'role_id');
        $relation->attach($role);

        $roles = $relation->get();
        $this->assertCount(1, $roles);
        $this->assertEquals('Admin', $roles[0]->name);
    }

    #[Test]
    public function belongs_to_many_attach_prevents_duplicates(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $role = TestRole::create(['id' => 1, 'name' => 'Admin']);

        $relation = new BelongsToMany($user, TestRole::class, 'role_user', 'user_id', 'role_id');
        $relation->attach($role);
        $relation->attach($role); // Attach same role twice

        $roles = $relation->get();
        $this->assertCount(1, $roles);
    }

    #[Test]
    public function belongs_to_many_detach_single(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $role1 = TestRole::create(['id' => 1, 'name' => 'Admin']);
        $role2 = TestRole::create(['id' => 2, 'name' => 'User']);

        $relation = new BelongsToMany($user, TestRole::class, 'role_user', 'user_id', 'role_id');
        $relation->attach($role1);
        $relation->attach($role2);
        $relation->detach($role1);

        $roles = $relation->get();
        $this->assertCount(1, $roles);
        $this->assertEquals('User', $roles[0]->name);
    }

    #[Test]
    public function belongs_to_many_detach_all(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $role1 = TestRole::create(['id' => 1, 'name' => 'Admin']);
        $role2 = TestRole::create(['id' => 2, 'name' => 'User']);

        $relation = new BelongsToMany($user, TestRole::class, 'role_user', 'user_id', 'role_id');
        $relation->attach($role1);
        $relation->attach($role2);
        $relation->detach(); // Detach all

        $this->assertEmpty($relation->get());
    }

    #[Test]
    public function belongs_to_many_sync(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $role1 = TestRole::create(['id' => 1, 'name' => 'Admin']);
        $role2 = TestRole::create(['id' => 2, 'name' => 'User']);
        $role3 = TestRole::create(['id' => 3, 'name' => 'Guest']);

        $relation = new BelongsToMany($user, TestRole::class, 'role_user', 'user_id', 'role_id');
        $relation->attach($role1);
        $relation->attach($role2);
        $relation->sync([2, 3]); // Sync to User and Guest

        $roles = $relation->get();
        $roleNames = array_map(fn($r) => $r->name, $roles);
        sort($roleNames);
        $this->assertEquals(['Guest', 'User'], $roleNames);
    }

    #[Test]
    public function belongs_to_many_toggle(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $role = TestRole::create(['id' => 1, 'name' => 'Admin']);

        $relation = new BelongsToMany($user, TestRole::class, 'role_user', 'user_id', 'role_id');
        
        // First toggle: attach
        $relation->toggle($role);
        $this->assertCount(1, $relation->get());

        // Second toggle: detach
        $relation->toggle($role);
        $this->assertEmpty($relation->get());
    }

    #[Test]
    public function belongs_to_many_count_with_attached(): void
    {
        $user = TestUser::create(['id' => 1, 'name' => 'John']);
        $role1 = TestRole::create(['id' => 1, 'name' => 'Admin']);
        $role2 = TestRole::create(['id' => 2, 'name' => 'User']);

        $relation = new BelongsToMany($user, TestRole::class, 'role_user', 'user_id', 'role_id');
        $relation->attach($role1);
        $relation->attach($role2);

        $this->assertEquals(2, $relation->count());
    }
}
