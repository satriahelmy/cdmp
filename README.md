# CDMP Exam Simulator

Local, single-learner Laravel application for timed mock exams and practice sessions. The question bank is imported from the supplied JSON file; it is not read at runtime.

## Local setup with XAMPP

1. Start Apache and MySQL in XAMPP and create the `cdmp` database.
2. Copy `.env.example` to `.env`, then set the local MySQL username and password. Keep `.env` private; it is ignored by Git.
3. Install PHP dependencies and generate the application key:

   ```bash
   composer install
   php artisan key:generate
   ```

4. Install frontend dependencies and build the assets:

   ```bash
   npm install
   npm run build
   ```

5. Create the tables and import the 193 ready questions:

   ```bash
   php artisan migrate
   php artisan db:seed --class=QuestionSeeder
   ```

   The seeder validates the whole JSON file before writing, skips review-queue records, and is safe to run again. It reports inserted, updated, unchanged, and skipped counts.

6. Point Apache's document root to this project's `public` directory. For a quick local preview, use Laravel's development server:

   ```bash
   php artisan serve
   ```

   Then open the URL printed by Artisan. With the default XAMPP folder layout, the project is also available at `http://localhost/cdmp/public` when Apache permits the `public` directory's `.htaccess` rules.

## Notes

- The app uses MySQL and stores attempts and answers in the database. Existing attempts keep question, option, key, explanation, and source snapshots even if the question bank changes later.
- Full exams contain 100 unique questions: ESL has 110 minutes and Standard has 90 minutes. Practice allows 20 or 50 questions with a configurable timer or untimed mode.
- Answer keys and explanations remain server-side until an attempt is submitted. Source answer keys have not all been independently verified against DAMA-DMBOK.
- This MVP has no login and is intended for localhost use. Add single-user authentication before exposing it on a network.
