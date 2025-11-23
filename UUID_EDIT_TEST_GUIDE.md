# 🧪 UUID Edit Page - Test Guide

## Quick Test

### Step 1: Access Dashboard
```
https://csv.mnr.world/index.php
```

### Step 2: Click Edit Button
- See the file list with "Edit" button
- Click Edit on any file
- **Expected**: URL changes to `edit.php?uuid=XXXXXXXXXXXX`

### Step 3: Verify Page Loads
- Should see question list
- Should see UUID displayed (e.g., "UUID: 2411231830")
- Form controls visible (Save, Add Question, Back)

### Step 4: Edit a Question
- Click on a question
- Change the question text
- Scroll to top
- Click "Save All Changes"

### Step 5: Verify Save
- Should see success message
- Should stay on same page (redirects back with UUID)
- Changes should be visible in question list

---

## Detailed Test Cases

### Test Case 1: Load via Dashboard
```
1. Visit: https://csv.mnr.world/index.php
2. Look for file with "Edit" button
3. Click Edit button
4. Verify:
   ✓ URL is: edit.php?uuid=XXXXXXXXXX (10 digits)
   ✓ Page loads without error
   ✓ Questions visible
   ✓ UUID badge shows at top
   ✓ Form controls present
```

### Test Case 2: Direct UUID Access
```
1. Note a file's UUID from dashboard (e.g., 2411231830)
2. Visit: https://csv.mnr.world/edit.php?uuid=2411231830
3. Verify:
   ✓ Edit page loads
   ✓ Correct file is loaded
   ✓ Questions match the file
   ✓ UUID badge matches
```

### Test Case 3: Invalid UUID Format
```
1. Visit: https://csv.mnr.world/edit.php?uuid=123
   (Less than 10 digits)
2. Verify:
   ✓ Error message shown
   ✓ Redirected to dashboard
   ✓ User still logged in
```

### Test Case 4: Non-Existent UUID
```
1. Visit: https://csv.mnr.world/edit.php?uuid=9999999999
   (Valid format but doesn't exist)
2. Verify:
   ✓ Error message: "File not found with UUID..."
   ✓ Redirected to dashboard
```

### Test Case 5: Edit and Save
```
1. Load edit page with valid UUID
2. Click on first question
3. Modify: Change question text
4. Scroll to top
5. Click "Save All Changes"
6. Verify:
   ✓ Success message shown
   ✓ Redirected back to edit.php?uuid=XXXX
   ✓ Question text is updated
   ✓ Change persists on reload
```

### Test Case 6: Add New Question
```
1. Load edit page
2. Click "Add Question" button
3. Fill in question details:
   - Question text
   - Options A-D (E optional)
   - Select correct answer
   - Add explanation
   - Type and section
4. Click "Save All Changes"
5. Verify:
   ✓ New question added
   ✓ Appears at end of list
   ✓ Question count updated
   ✓ Changes persist
```

### Test Case 7: Delete Question
```
1. Load edit page
2. Find a question to delete
3. Click "Delete" button on that question
4. Click "Save All Changes"
5. Verify:
   ✓ Question removed from list
   ✓ Question count updated
   ✓ Changes persist on reload
```

### Test Case 8: Backward Compatibility (Optional)
```
1. Visit: https://csv.mnr.world/edit.php?id=1
   (Old numeric ID format)
2. Verify:
   ✓ Edit page still loads
   ✓ Works as before
   ✓ Shows file content
```

### Test Case 9: Multiple Files
```
1. Dashboard: https://csv.mnr.world/index.php
2. For each file:
   - Click Edit
   - Verify correct UUID in URL
   - Verify correct file loaded
3. Switch between files
4. Verify:
   ✓ Each file has unique UUID
   ✓ Correct content loads each time
```

### Test Case 10: Session Persistence
```
1. Log in
2. Open edit page: edit.php?uuid=2411231830
3. Keep page open for a while
4. Try to edit and save
5. Verify:
   ✓ Session still active
   ✓ Save operation works
   ✓ No login required again
```

---

## Expected Behavior Summary

| Action | Expected Result |
|--------|-----------------|
| Visit index.php | See file list with Edit buttons |
| Click Edit button | Load edit.php?uuid=XXXX |
| View edit page | Show file UUID and questions |
| Edit question | Changes in textarea |
| Save changes | Success message, page refreshes |
| Add question | New empty question added |
| Delete question | Question marked for deletion |
| Save deletions | Questions removed from list |
| Invalid UUID format | Error message, redirect to dashboard |
| Non-existent UUID | Error message, redirect to dashboard |

---

## Debugging

### If Edit Button Shows Wrong URL:
- Check that index.php was updated correctly
- Verify file_uuid column has values in database
- Restart browser cache

### If Edit Page Shows 403 Error:
- Check you're logged in (see PHPSESSID cookie)
- Verify UUID format is correct (10 digits)
- Check file exists in database

### If Save Doesn't Work:
- Check database connection
- Verify file permissions on server
- Check file_id lookup is working
- Look at error message in red box

### If Questions Don't Load:
- Check JSON in database is valid
- Verify file_uuid value exists
- Try with different file

---

## Success Criteria

✅ Can access edit page via UUID parameter  
✅ Page shows correct file and questions  
✅ Can edit questions and save changes  
✅ UUID parameter used in all redirects  
✅ Dashboard Edit button uses UUID  
✅ Invalid UUIDs show error messages  
✅ Session persists throughout workflow  
✅ Multiple files work independently  

---

**Test Date**: 2025-11-23  
**Feature**: UUID-based Edit Page  
**Status**: Ready for Testing
