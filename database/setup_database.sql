CREATE TABLE IF NOT EXISTS "claims" (
    "claimID" INTEGER PRIMARY KEY AUTOINCREMENT,
    "userID" INTEGER,
    "department_id" INTEGER,
    "category_id" INTEGER,
    "amount" DECIMAL(10,2) NOT NULL,
    "currency" VARCHAR(3) DEFAULT 'KSH',
    "description" TEXT,
    "purpose" TEXT,
    "status" VARCHAR(20) DEFAULT 'Draft',
    "submission_date" TIMESTAMP,
    "incurred_date" DATE,
    "receipt_path" TEXT,
    "mpesa_code" VARCHAR(30),
    "additional_docs" TEXT,
    "reference_number" VARCHAR(20),
    "notes" TEXT,
    "approverID" INTEGER,
    "approval_date" TIMESTAMP,
    "rejection_reason" TEXT,
    "payment_reference" VARCHAR(50),
    "payment_date" TIMESTAMP,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "last_updated" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "project_id" INTEGER,
    "billable_to_client" INTEGER DEFAULT 0,
    FOREIGN KEY ("userID") REFERENCES "users"("userID"),
    FOREIGN KEY ("approverID") REFERENCES "users"("userID"),
    FOREIGN KEY ("department_id") REFERENCES "departments"("department_id"),
    FOREIGN KEY ("category_id") REFERENCES "expense_categories"("category_id")
);

CREATE TABLE IF NOT EXISTS "approval_workflows" (
    "workflow_id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "workflow_name" VARCHAR(100) NOT NULL,
    "department_id" INTEGER,
    "category_id" INTEGER,
    "min_amount" DECIMAL(15, 2) DEFAULT 0.00,
    "max_amount" DECIMAL(15, 2),
    "is_active" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("department_id") REFERENCES "departments"("department_id"),
    FOREIGN KEY ("category_id") REFERENCES "expense_categories"("category_id")
);

CREATE TABLE IF NOT EXISTS "approval_steps" (
    "step_id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "workflow_id" INTEGER NOT NULL,
    "step_order" INTEGER NOT NULL,
    "approver_role" VARCHAR(20) NOT NULL,
    "specific_approver_id" INTEGER,
    "is_mandatory" INTEGER DEFAULT 1,
    "approval_timeout_hours" INTEGER DEFAULT 48,
    "escalation_after_hours" INTEGER DEFAULT 72,
    "escalation_to" INTEGER,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("workflow_id") REFERENCES "approval_workflows"("workflow_id"),
    FOREIGN KEY ("specific_approver_id") REFERENCES "users"("userID"),
    FOREIGN KEY ("escalation_to") REFERENCES "users"("userID")
);

CREATE TABLE IF NOT EXISTS "claim_approvals" (
    "approval_id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "claimID" INTEGER NOT NULL,
    "step_id" INTEGER NOT NULL,
    "approver_id" INTEGER,
    "status" VARCHAR(20) DEFAULT 'Pending',
    "decision_date" DATETIME,
    "comments" TEXT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("claimID") REFERENCES "claims"("claimID"),
    FOREIGN KEY ("step_id") REFERENCES "approval_steps"("step_id"),
    FOREIGN KEY ("approver_id") REFERENCES "users"("userID")
);

CREATE TABLE IF NOT EXISTS "policies" (
    "policy_id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "policy_name" VARCHAR(100) NOT NULL,
    "policy_description" TEXT,
    "policy_document" VARCHAR(255),
    "effective_date" DATE,
    "expiry_date" DATE,
    "created_by" INTEGER,
    "is_active" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("created_by") REFERENCES "users"("userID")
);

CREATE TABLE IF NOT EXISTS "user_tokens" (
    "token_id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "userID" INTEGER NOT NULL,
    "token" VARCHAR(255) NOT NULL,
    "token_type" VARCHAR(20) DEFAULT 'remember',
    "expires_at" TIMESTAMP NOT NULL,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("userID") REFERENCES "users"("userID")
);

CREATE TABLE IF NOT EXISTS "projects" (
    "project_id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "project_code" VARCHAR(20) UNIQUE,
    "project_name" VARCHAR(100) NOT NULL,
    "client_id" INTEGER,
    "budget" DECIMAL(15, 2),
    "start_date" DATE,
    "end_date" DATE,
    "is_active" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("client_id") REFERENCES "clients"("client_id")
);

CREATE TABLE IF NOT EXISTS "clients" (
    "client_id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "client_name" VARCHAR(100) NOT NULL,
    "client_code" VARCHAR(20) UNIQUE,
    "contact_person" VARCHAR(100),
    "contact_email" VARCHAR(100),
    "contact_phone" VARCHAR(20),
    "billing_address" TEXT,
    "is_active" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "messages" (
    "message_id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "sender_id" INTEGER NOT NULL,
    "recipient_id" INTEGER NOT NULL,
    "subject" TEXT NOT NULL,
    "message_text" TEXT NOT NULL,
    "is_read" INTEGER DEFAULT 0,
    "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("sender_id") REFERENCES "users"("userID"),
    FOREIGN KEY ("recipient_id") REFERENCES "users"("userID")
);

CREATE TABLE IF NOT EXISTS "message_replies" (
    "reply_id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "message_id" INTEGER NOT NULL,
    "sender_id" INTEGER NOT NULL,
    "reply_text" TEXT NOT NULL,
    "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("message_id") REFERENCES "messages"("message_id"),
    FOREIGN KEY ("sender_id") REFERENCES "users"("userID")
);

CREATE INDEX IF NOT EXISTS "idx_messages_sender" ON "messages"("sender_id");
CREATE INDEX IF NOT EXISTS "idx_messages_recipient" ON "messages"("recipient_id");
CREATE INDEX IF NOT EXISTS "idx_message_replies" ON "message_replies"("message_id"); 