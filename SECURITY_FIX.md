# Git History Cleanup Instructions

To maintain a clean git history, follow these steps:

### 1. Rebase instead of merge
When integrating changes from the main branch, prefer using rebase instead of merge:
```bash
git checkout feature-branch
git rebase main
```

### 2. Squash commits
When preparing to merge a feature branch, squash commits to reduce noise in the history:
```bash
git rebase -i main
```
Select `s` (squash) for commits you want to combine.

### 3. Use meaningful commit messages
Always write clear, descriptive commit messages, e.g., `Fix bug in user authentication flow.`

### 4. Remove sensitive data from history
If sensitive data was accidentally committed, run:
```bash
git filter-branch --force --index-filter 
'git rm --cached --ignore-unmatch path/to/sensitive_file' \
--prune-empty --tag-name-filter cat -- --all
```

### 5. Force push after cleanup
After cleaning up the history, force push the changes:
```bash
git push origin branch-name --force
```
```