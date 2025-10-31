# MCP Tools Test Suite

Comprehensive test coverage for SmartCms Kit MCP (Model Context Protocol) tools.

## Overview

This test suite validates all 21 MCP tools across 5 test files:

- **PageManagementTest.php** - Page CRUD operations (8 tools tested)
- **SeoManagementTest.php** - SEO metadata management (1 tool tested)
- **BlockManagementTest.php** - Block schemas and data management (5 tools tested)
- **VariableTypeTest.php** - Variable type system (2 tools tested)
- **SiteConfigurationTest.php** - Languages and menus (3 tools tested)
- **IntegrationTest.php** - End-to-end workflows and edge cases

## Running Tests

### Run All MCP Tests

```bash
composer test -- tests/Mcp
```

### Run Specific Test File

```bash
composer test -- tests/Mcp/PageManagementTest.php
```

### Run Specific Test Method

```bash
composer test -- --filter it_can_create_page
```

### Run with Coverage

```bash
composer test-coverage -- tests/Mcp
```

## Test Structure

All tests follow this pattern:

```php
/** @test */
public function it_can_do_something()
{
    // 1. Setup test data
    $page = Page::factory()->create();

    // 2. Execute MCP tool
    $tool = app(UpdatePageSeo::class);
    $response = $tool->handle(new Request([
        'page_id' => $page->id,
        'meta_title' => 'Test Title',
    ]));

    // 3. Decode JSON response
    $result = $this->decodeResponse($response);

    // 4. Assert expectations
    $this->assertTrue($result['success']);
    $this->assertEquals('Test Title', $result['page']['seo']['meta_title']);

    // 5. Verify database state
    $this->assertDatabaseHas('pages', [
        'id' => $page->id,
        'meta_title' => 'Test Title',
    ]);
}
```

## Helper Method

All test classes include this helper to decode MCP responses:

```php
protected function decodeResponse($response): array
{
    $content = $response->content[0]['text'] ?? '{}';
    return json_decode($content, true);
}
```

## Test Categories

### 1. Page Management Tests (13 tests)

Tests for page CRUD operations:

- ✅ List pages with filtering
- ✅ Get page by ID/slug
- ✅ Create page with validation
- ✅ Update page metadata
- ✅ Publish/unpublish workflow
- ✅ Delete pages
- ✅ Error handling for invalid data

**Tools Tested:**
- `GetPages`, `GetPage`, `CreatePage`, `UpdatePage`
- `PublishPage`, `UnpublishPage`, `DeletePage`

### 2. SEO Management Tests (7 tests)

Tests for SEO metadata:

- ✅ Update meta tags (title, description, keywords)
- ✅ Update OpenGraph tags
- ✅ Validation for field lengths
- ✅ Partial field updates
- ✅ Error handling

**Tools Tested:**
- `UpdatePageSeo`

### 3. Block Management Tests (12 tests)

Tests for block schemas and data:

- ✅ List all block schemas
- ✅ Get specific block schema
- ✅ Get block instance with data
- ✅ Create block instances
- ✅ Update block data with merge mode
- ✅ Update block data with replace mode
- ✅ Multilingual block support
- ✅ Error handling

**Tools Tested:**
- `GetBlockSchemas`, `GetBlockSchema`, `GetBlock`
- `CreateBlockInstance`, `UpdateBlockData`

### 4. Variable Type Tests (6 tests)

Tests for variable type system:

- ✅ List all registered variable types
- ✅ Verify common types (string, menu, link, image)
- ✅ Get specific type schema
- ✅ Validate default values
- ✅ Error handling for invalid types

**Tools Tested:**
- `GetVariableTypes`, `GetVariableTypeSchema`

### 5. Site Configuration Tests (9 tests)

Tests for site-wide settings:

- ✅ Get configured languages
- ✅ List menus with filtering
- ✅ Get menu by ID/name
- ✅ Menu item counting
- ✅ Error handling

**Tools Tested:**
- `GetLanguages`, `GetMenus`, `GetMenu`

### 6. Integration Tests (7 tests)

End-to-end workflows:

- ✅ Page creation + SEO update workflow
- ✅ Page variables with blocks
- ✅ Custom variable types preservation (menu IDs)
- ✅ Multilingual block creation
- ✅ Required field validation
- ✅ Data merging scenarios

**Scenarios Tested:**
- Complete page setup workflow
- Block attachment and retrieval
- Variable type handling during updates
- Schema validation

## Coverage Goals

- **Tool Coverage**: 21/21 tools (100%)
- **Success Scenarios**: All major features tested
- **Error Handling**: Validation failures, not found errors, invalid data
- **Edge Cases**: Empty data, null values, type mismatches
- **Integration**: Multi-step workflows

## Writing New Tests

### Adding Tests for New Tools

1. **Create test method** with descriptive name:
   ```php
   /** @test */
   public function it_can_do_new_feature()
   ```

2. **Follow AAA pattern**:
   - **Arrange**: Set up test data
   - **Act**: Execute the tool
   - **Assert**: Verify results

3. **Test both success and failure cases**

4. **Use factories** for test data:
   ```php
   $page = Page::factory()->create(['status' => true]);
   ```

5. **Decode response** using helper method:
   ```php
   $result = $this->decodeResponse($response);
   ```

### Example: Testing a New Tool

```php
/** @test */
public function it_can_export_page()
{
    // Arrange
    $page = Page::factory()->create([
        'title' => 'Export Test',
    ]);

    // Act
    $tool = app(ExportPage::class);
    $response = $tool->handle(new Request([
        'page_id' => $page->id,
        'include_blocks' => true,
    ]));

    // Assert
    $result = $this->decodeResponse($response);

    $this->assertTrue($result['success']);
    $this->assertEquals('Export Test', $result['export']['title']);
    $this->assertArrayHasKey('blocks', $result['export']);
}
```

## Testing Best Practices

1. **Use RefreshDatabase** - Each test runs with fresh database
2. **Factory Usage** - Use factories instead of direct model creation
3. **Descriptive Names** - Test names should read like documentation
4. **Single Assertion Focus** - Each test should verify one behavior
5. **Arrange-Act-Assert** - Follow AAA pattern consistently
6. **Error Testing** - Always test failure scenarios
7. **Data Validation** - Test validation rules thoroughly

## Common Test Patterns

### Testing Success Response

```php
$result = $this->decodeResponse($response);

$this->assertTrue($result['success']);
$this->assertArrayHasKey('data', $result);
```

### Testing Error Response

```php
$result = $this->decodeResponse($response);

$this->assertFalse($result['success']);
$this->assertArrayHasKey('error', $result);
```

### Testing Database State

```php
$this->assertDatabaseHas('pages', [
    'id' => $page->id,
    'title' => 'Updated Title',
]);
```

### Testing Validation

```php
$response = $tool->handle(new Request([
    'title' => str_repeat('a', 256), // Exceeds max length
]));

$result = $this->decodeResponse($response);
$this->assertFalse($result['success']);
```

## Debugging Failed Tests

### View Full Response

```php
dd($this->decodeResponse($response));
```

### Check Database State

```php
$this->assertDatabaseHas('pages', [/* ... */]);
```

### Enable Debug Mode

In `phpunit.xml`:
```xml
<env name="APP_DEBUG" value="true"/>
```

## CI/CD Integration

These tests are designed to run in CI environments:

```yaml
# .github/workflows/tests.yml
- name: Run MCP Tests
  run: composer test -- tests/Mcp
```

## Factories Required

Ensure these factories exist:

- `PageFactory` - For creating test pages
- `BlockFactory` - For creating test blocks
- `MenuFactory` - For creating test menus

## Dependencies

Tests require:

- Laravel MCP package (`laravel/mcp`)
- PHPUnit
- Orchestra Testbench (for package testing)
- SmartCms Kit models and services

## Troubleshooting

### "Language service not available"

Some tests require the language service. If unavailable, tests will be skipped:

```php
if (! app()->has('lang')) {
    $this->markTestSkipped('Language service not available');
}
```

### "Table doesn't exist"

Ensure migrations are run:

```bash
php artisan migrate --env=testing
```

### Factory not found

Create missing factories in `tests/Factories/`.

## Future Enhancements

- [ ] Add performance benchmarks
- [ ] Test MCP server configuration
- [ ] Test tool permissions/authorization
- [ ] Add mutation testing
- [ ] Test concurrent requests
- [ ] Add visual regression tests for block rendering

## Contributing

When adding new MCP tools:

1. Write tests first (TDD)
2. Achieve 100% code coverage
3. Test success + error scenarios
4. Update this README

## Questions?

See the main `MCP_SETUP.md` for MCP server documentation.
