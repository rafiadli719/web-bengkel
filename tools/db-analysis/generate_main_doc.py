import json

# Read all analysis files
with open('database_categorization.json', 'r', encoding='utf-8') as f:
    categorization = json.load(f)

with open('table_analysis.json', 'r', encoding='utf-8') as f:
    tables = json.load(f)

with open('important_tables_detail.json', 'r', encoding='utf-8') as f:
    detailed_tables = json.load(f)

with open('database_relationships.json', 'r', encoding='utf-8') as f:
    relationships = json.load(f)

with open('naming_analysis.json', 'r', encoding='utf-8') as f:
    naming = json.load(f)

# Generate comprehensive markdown documentation
doc = []

# Header
doc.append("# DATABASE STRUCTURE ANALYSIS")
doc.append("## FITMOTOR BENGKEL MANAGEMENT SYSTEM")
doc.append("")
doc.append("**Generated:** 2025-11-28  ")
doc.append("**Database:** fitmotor_dbbengkel  ")
doc.append("**Total Tables:** " + str(categorization['total_tables']))
doc.append("")
doc.append("---")
doc.append("")

# Table of Contents
doc.append("## Table of Contents")
doc.append("")
doc.append("1. [Executive Summary](#executive-summary)")
doc.append("2. [Database Overview](#database-overview)")
doc.append("3. [Table Categories](#table-categories)")
doc.append("4. [Core Modules](#core-modules)")
doc.append("5. [Detailed Table Analysis](#detailed-table-analysis)")
doc.append("6. [Relationships & Dependencies](#relationships--dependencies)")
doc.append("7. [Naming Conventions](#naming-conventions)")
doc.append("8. [Database Design Issues](#database-design-issues)")
doc.append("9. [Recommendations](#recommendations)")
doc.append("")
doc.append("---")
doc.append("")

# Executive Summary
doc.append("## Executive Summary")
doc.append("")
doc.append("This document provides a comprehensive analysis of the FitMotor Bengkel database structure, ")
doc.append("which manages all aspects of a motorcycle workshop including service, sales, purchases, ")
doc.append("inventory, and customer management.")
doc.append("")
doc.append("### Key Statistics")
doc.append("")
doc.append(f"- **Total Tables:** {categorization['total_tables']}")
doc.append(f"- **Master Data Tables:** {categorization['categories']['Master Data']}")
doc.append(f"- **Transaction Tables:** {categorization['categories']['Transaksi']}")
doc.append(f"- **Views:** {categorization['categories']['Views']}")
doc.append(f"- **System Tables:** {categorization['categories']['System & Configuration']}")
doc.append("")
doc.append("### Critical Issues Identified")
doc.append("")
doc.append(f"- {len(naming['design_issues']['no_primary_key'])} tables without primary keys")
doc.append(f"- {len(naming['design_issues']['missing_timestamps'])} tables without timestamp columns")
doc.append("- Limited use of foreign key constraints")
doc.append("- Inconsistent naming conventions")
doc.append("")
doc.append("---")
doc.append("")

# Database Overview
doc.append("## Database Overview")
doc.append("")
doc.append("The FitMotor Bengkel database is designed to support the complete operations of a motorcycle ")
doc.append("service center. It encompasses:")
doc.append("")
doc.append("### Core Business Functions")
doc.append("")
doc.append("1. **Customer & Vehicle Management**")
doc.append("   - Customer registration and profile management")
doc.append("   - Vehicle registration linked to customers")
doc.append("   - Customer categorization and membership tiers")
doc.append("")
doc.append("2. **Service Management**")
doc.append("   - Service order creation and tracking")
doc.append("   - Work order management")
doc.append("   - Complaint/issue tracking (keluhan)")
doc.append("   - Service findings and recommendations (temuan)")
doc.append("   - Parts and labor tracking")
doc.append("")
doc.append("3. **Inventory Management**")
doc.append("   - Product/item master data")
doc.append("   - Stock tracking across branches")
doc.append("   - Stock movement (in/out)")
doc.append("   - Item categorization")
doc.append("")
doc.append("4. **Sales & Purchase**")
doc.append("   - Sales transactions")
doc.append("   - Purchase orders")
doc.append("   - Supplier management")
doc.append("   - Returns processing")
doc.append("")
doc.append("5. **Procurement Chain**")
doc.append("   - Purchase Requests (PR)")
doc.append("   - Purchase Orders (PO)")
doc.append("   - Delivery Orders (DO)")
doc.append("   - Approval workflows")
doc.append("")
doc.append("6. **Financial Management**")
doc.append("   - Accounts receivable (piutang)")
doc.append("   - Accounts payable (hutang)")
doc.append("   - Cash management")
doc.append("")
doc.append("7. **HR & Payroll**")
doc.append("   - Employee management")
doc.append("   - Attendance tracking")
doc.append("   - Salary processing")
doc.append("")
doc.append("8. **User Management & RBAC**")
doc.append("   - User accounts")
doc.append("   - Role-based access control")
doc.append("   - Permission management")
doc.append("   - Activity logging")
doc.append("")
doc.append("---")
doc.append("")

# Table Categories
doc.append("## Table Categories")
doc.append("")
doc.append("Tables are organized into the following functional categories:")
doc.append("")

for category, count in categorization['categories'].items():
    doc.append(f"### {category} ({count} tables)")
    doc.append("")

    table_list = categorization['categorized_tables'].get(category, [])
    if table_list:
        # Group by prefix for better organization
        for table in sorted(table_list)[:20]:  # Limit to first 20 per category
            table_info = tables.get(table, {})
            col_count = len(table_info.get('columns', []))
            pk = table_info.get('primary_key', None)
            pk_status = "PK" if pk else "NO PK"
            doc.append(f"- `{table}` ({col_count} columns) [{pk_status}]")

        if len(table_list) > 20:
            doc.append(f"- ... and {len(table_list) - 20} more tables")

    doc.append("")

doc.append("---")
doc.append("")

# Core Modules - Detailed
doc.append("## Core Modules")
doc.append("")

# Module 1: Customer & Vehicle
doc.append("### Module 1: Customer & Vehicle Management")
doc.append("")
doc.append("#### Primary Tables")
doc.append("")
doc.append("**TBLPELANGGAN (Customer Master)**")
doc.append("")

if 'tblpelanggan' in detailed_tables:
    pelanggan = detailed_tables['tblpelanggan']
    doc.append(f"- **Total Columns:** {len(pelanggan['columns'])}")
    doc.append(f"- **Primary Key:** {', '.join(pelanggan['primary_keys']) if pelanggan['primary_keys'] else 'NOT DEFINED'}")
    doc.append("- **Key Columns:**")

    key_cols = ['no_pelanggan', 'nama_pelanggan', 'alamat', 'telepon', 'hp', 'email', 'kategori']
    for col_data in pelanggan['columns'][:15]:
        col_name = col_data['name']
        col_def = col_data['definition'][:50]
        doc.append(f"  - `{col_name}`: {col_def}")

doc.append("")
doc.append("**TBLKENDARAAN (Vehicle Registration)**")
doc.append("")

if 'tblkendaraan' in detailed_tables:
    kendaraan = detailed_tables['tblkendaraan']
    doc.append(f"- **Total Columns:** {len(kendaraan['columns'])}")
    doc.append(f"- **Primary Key:** {', '.join(kendaraan['primary_keys']) if kendaraan['primary_keys'] else 'NOT DEFINED'}")
    doc.append("- **Key Columns:**")

    for col_data in kendaraan['columns'][:10]:
        col_name = col_data['name']
        col_def = col_data['definition'][:50]
        doc.append(f"  - `{col_name}`: {col_def}")

doc.append("")
doc.append("#### Relationships")
doc.append("")
doc.append("```")
doc.append("TBLPELANGGAN (1) ----< (*) TBLKENDARAAN")
doc.append("TBLPELANGGAN (1) ----< (*) TBLSERVICE")
doc.append("TBLKENDARAAN (1) ----< (*) TBLSERVICE")
doc.append("```")
doc.append("")

# Module 2: Service Management
doc.append("### Module 2: Service Management")
doc.append("")
doc.append("#### Primary Tables")
doc.append("")
doc.append("**TBLSERVICE (Service Transaction Header)**")
doc.append("")

if 'tblservice' in detailed_tables:
    service = detailed_tables['tblservice']
    doc.append(f"- **Total Columns:** {len(service['columns'])}")
    doc.append(f"- **Primary Key:** {', '.join(service['primary_keys']) if service['primary_keys'] else 'NOT DEFINED'}")
    doc.append(f"- **Indexes:** {len(service['indexes'])}")
    doc.append("- **Key Columns:**")

    for col_data in service['columns'][:20]:
        col_name = col_data['name']
        doc.append(f"  - `{col_name}`")

doc.append("")
doc.append("**Service Detail Tables:**")
doc.append("")
doc.append("- `TBLSERVIS_BARANG` - Parts used in service")
doc.append("- `TBLSERVIS_JASA` - Labor/services performed")
doc.append("- `TBSERVIS_KELUHAN` - Customer complaints/issues")
doc.append("- `TBSERVIS_TEMUAN` - Service findings/recommendations")
doc.append("- `TBSERVIS_WORKORDER` - Work order details")
doc.append("")

# Module 3: Inventory
doc.append("### Module 3: Inventory Management")
doc.append("")
doc.append("**TBLITEM (Product/Item Master)**")
doc.append("")

if 'tblitem' in detailed_tables:
    item = detailed_tables['tblitem']
    doc.append(f"- **Total Columns:** {len(item['columns'])}")
    doc.append(f"- **Primary Key:** {', '.join(item['primary_keys']) if item['primary_keys'] else 'NOT DEFINED'}")
    doc.append("- **Related Tables:**")
    doc.append("  - `TBLITEM_STOK` - Stock levels per branch")
    doc.append("  - `TBLITEM_JASA` - Service items")
    doc.append("  - `TBLITEM_SPART` - Spare parts mapping")
    doc.append("  - `TBSTOK` - Stock transactions")

doc.append("")

# Module 4: Sales & Purchase
doc.append("### Module 4: Sales & Purchase")
doc.append("")
doc.append("#### Purchase Transactions")
doc.append("")
doc.append("**Header-Detail Structure:**")
doc.append("")
doc.append("- `TBLPEMBELIAN_HEADER` (1) ----< (*) `TBLPEMBELIAN_DETAIL`")
doc.append("- Linked to: `TBLSUPPLIER`")
doc.append("")
doc.append("#### Sales Transactions")
doc.append("")
doc.append("- `TBLPENJUALAN_HEADER` (1) ----< (*) `TBLPENJUALAN_DETAIL`")
doc.append("- Linked to: `TBLPELANGGAN`")
doc.append("")

# Module 5: Procurement
doc.append("### Module 5: Procurement Chain")
doc.append("")
doc.append("The procurement process follows this workflow:")
doc.append("")
doc.append("```")
doc.append("1. Purchase Request (PR)")
doc.append("   TBLPURCHASE_REQUEST_HEADER -> TBLPURCHASE_REQUEST_DETAIL")
doc.append("")
doc.append("2. Purchase Order (PO)")
doc.append("   TBLORDER_HEADER -> TBLORDER_DETAIL")
doc.append("")
doc.append("3. Delivery Order (DO)")
doc.append("   TBLDELIVERY_ORDER_HEADER -> TBLDELIVERY_ORDER_DETAIL")
doc.append("```")
doc.append("")
doc.append("**Approval & Tracking:**")
doc.append("")
doc.append("- `TBLPO_APPROVAL_LOG` - PO approval history")
doc.append("- `TBLPR_APPROVAL_LOG` - PR approval history")
doc.append("- `TBLPR_TRACKING` - PR tracking")
doc.append("- `TBLDO_TRACKING` - DO tracking")
doc.append("")

doc.append("---")
doc.append("")

# Relationships
doc.append("## Relationships & Dependencies")
doc.append("")
doc.append("### Header-Detail Pairs")
doc.append("")
doc.append("The following tables follow a standard header-detail pattern:")
doc.append("")

for pair in relationships['header_detail_pairs']:
    doc.append(f"- `{pair['header']}` (1) ----< (*) `{pair['detail']}`")

doc.append("")
doc.append("### Core Entity Relationships")
doc.append("")

for entity, rel_data in relationships['core_entity_relationships'].items():
    doc.append(f"**{entity}**")
    doc.append(f"- Description: {rel_data.get('description', 'N/A')}")

    if 'belongs_to' in rel_data:
        doc.append(f"- Belongs to: {', '.join(rel_data['belongs_to'])}")

    if 'has_many' in rel_data:
        doc.append(f"- Has many: {', '.join(rel_data['has_many'])}")

    doc.append("")

doc.append("---")
doc.append("")

# Naming Conventions
doc.append("## Naming Conventions")
doc.append("")
doc.append("### Table Name Patterns")
doc.append("")
doc.append("The database uses multiple naming patterns:")
doc.append("")

for prefix, count in naming['naming_patterns'].items():
    if count > 0:
        doc.append(f"- `{prefix}*` : {count} tables")

doc.append("")
doc.append("### Analysis")
doc.append("")
doc.append("**Issues Identified:**")
doc.append("")
doc.append("1. **Inconsistent Prefixes** - Mix of `tbl`, `tb`, `tbl_`, and no prefix")
doc.append("2. **Mixed Naming Styles** - Some tables use plural, others singular")
doc.append("3. **Redundant Prefixes** - Both `master_` and `tbmaster_` exist")
doc.append("")
doc.append("**Recommendations:**")
doc.append("")
doc.append("- Standardize to single prefix convention (suggested: `tbl_`)")
doc.append("- Use snake_case consistently")
doc.append("- Avoid abbreviations unless widely understood")
doc.append("- Document naming standards for future development")
doc.append("")

doc.append("---")
doc.append("")

# Database Design Issues
doc.append("## Database Design Issues")
doc.append("")
doc.append("### Critical Issues")
doc.append("")
doc.append(f"#### 1. Missing Primary Keys ({len(naming['design_issues']['no_primary_key'])} tables)")
doc.append("")
doc.append("Tables without primary keys:")
doc.append("")

for i, table in enumerate(sorted(naming['design_issues']['no_primary_key'])[:20], 1):
    doc.append(f"{i}. `{table}`")

if len(naming['design_issues']['no_primary_key']) > 20:
    doc.append(f"... and {len(naming['design_issues']['no_primary_key']) - 20} more")

doc.append("")
doc.append("**Impact:** Data integrity issues, slow queries, cannot establish proper relationships")
doc.append("")

doc.append(f"#### 2. Missing Timestamps ({len(naming['design_issues']['missing_timestamps'])} tables)")
doc.append("")
doc.append("**Impact:** Cannot track when records were created or modified")
doc.append("")

doc.append("#### 3. Missing Foreign Key Constraints")
doc.append("")
doc.append("**Impact:** No referential integrity enforcement at database level")
doc.append("")

doc.append("#### 4. Large Tables")
doc.append("")
doc.append("Tables with excessive columns (>40):")
doc.append("")

for large in naming['design_issues']['large_tables']:
    doc.append(f"- `{large['table']}`: {large['column_count']} columns")

doc.append("")

doc.append("---")
doc.append("")

# Recommendations
doc.append("## Recommendations")
doc.append("")
doc.append("### Priority 1: Critical (Immediate Action Required)")
doc.append("")
doc.append("1. **Add Primary Keys to All Tables**")
doc.append("   - Define appropriate primary keys for all 50 tables currently lacking them")
doc.append("   - Use AUTO_INCREMENT INT for surrogate keys where natural keys don't exist")
doc.append("   - For transaction tables, consider composite keys")
doc.append("")
doc.append("2. **Implement Foreign Key Constraints**")
doc.append("   - Add FK constraints for all identified relationships")
doc.append("   - Define appropriate ON DELETE and ON UPDATE actions")
doc.append("   - Ensures referential integrity at database level")
doc.append("")
doc.append("3. **Add Indexes on Foreign Key Columns**")
doc.append("   - Improves query performance significantly")
doc.append("   - Essential for JOIN operations")
doc.append("")
doc.append("### Priority 2: High (Within 1-2 Months)")
doc.append("")
doc.append("4. **Add Timestamp Columns**")
doc.append("   - Add `created_at` and `updated_at` to all tables")
doc.append("   - Consider adding `created_by` and `updated_by` for audit trail")
doc.append("")
doc.append("5. **Standardize Naming Conventions**")
doc.append("   - Choose one table prefix convention and stick to it")
doc.append("   - Rename tables following the standard")
doc.append("   - Update all application code accordingly")
doc.append("")
doc.append("6. **Create Database Documentation**")
doc.append("   - Document all tables, columns, and relationships")
doc.append("   - Maintain data dictionary")
doc.append("   - Document business rules and constraints")
doc.append("")
doc.append("### Priority 3: Medium (Within 3-6 Months)")
doc.append("")
doc.append("7. **Normalize Large Tables**")
doc.append("   - Review tables with >40 columns")
doc.append("   - Split into logical related tables")
doc.append("   - Reduce data redundancy")
doc.append("")
doc.append("8. **Optimize Indexes**")
doc.append("   - Analyze query patterns")
doc.append("   - Create composite indexes for common queries")
doc.append("   - Remove unused indexes")
doc.append("")
doc.append("9. **Clean Up Backup Tables**")
doc.append("   - Move backup tables to separate schema")
doc.append("   - Implement proper backup/restore procedures")
doc.append("   - Remove temporary tables from production")
doc.append("")
doc.append("### Priority 4: Low (Ongoing)")
doc.append("")
doc.append("10. **Performance Monitoring**")
doc.append("    - Implement query performance monitoring")
doc.append("    - Identify slow queries")
doc.append("    - Optimize based on actual usage patterns")
doc.append("")
doc.append("11. **Data Archival Strategy**")
doc.append("    - Implement data archival for old transactions")
doc.append("    - Keep production database lean")
doc.append("    - Maintain historical data in archive")
doc.append("")

doc.append("---")
doc.append("")
doc.append("## Appendix: Detailed Table List")
doc.append("")
doc.append("For a complete list of all tables with detailed column information, ")
doc.append("refer to `DATABASE_TABLE_LIST.txt`")
doc.append("")
doc.append("For entity relationship diagrams, refer to `DATABASE_ERD.txt`")
doc.append("")
doc.append("For complete list of issues, refer to `DATABASE_ISSUES.txt`")
doc.append("")
doc.append("---")
doc.append("")
doc.append("*Document generated automatically from database schema analysis*")
doc.append("")

# Write to file
with open('DATABASE_STRUCTURE_ANALYSIS.md', 'w', encoding='utf-8') as f:
    f.write('\n'.join(doc))

print(f"Generated DATABASE_STRUCTURE_ANALYSIS.md ({len(doc)} lines)")
