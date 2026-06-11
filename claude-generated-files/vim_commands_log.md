# Vim Course — Commands Log
Running record of every command introduced, organised by chapter.
Used to generate the final appendix cheat sheet.

---

## Chapter 1 — Introduction

| Command | Mode | Description |
|---|---|---|
| `vim --version` | Shell | Check Vim is installed; display version |
| `vim <filename>` | Shell | Open or create a file in Vim |
| `i` | Normal | Enter Insert mode at cursor |
| `Esc` | Any | Return to Normal mode |
| `:set number` | Command | Show line numbers |
| `:set nonumber` | Command | Hide line numbers |
| `:set relativenumber` | Command | Show relative line numbers |
| `:w` | Command | Write (save) the file |
| `:q` | Command | Quit (fails if unsaved changes) |
| `:q!` | Command | Force quit, discard changes |
| `:wq` | Command | Write and quit |

---

## Chapter 2 — Basics

| Command | Mode | Description |
|---|---|---|
| `Ctrl+C` | Insert/Visual | Alternative to `Esc` — return to Normal mode |
| `Ctrl+G` | Normal | Show filename, line count, and cursor position |
| `ZZ` | Normal | Write (if changed) and quit |
| `ZQ` | Normal | Force quit without saving |
| `Ctrl+W` | Insert | Delete word before cursor |
| `Ctrl+U` | Insert | Delete from cursor to start of line |
| `:e <file>` | Command | Open/edit a file (Tab completes filename) |
| `:e!` | Command | Reload current file from disk, discard changes |
| `:w <file>` | Command | Save buffer to a new filename (Save As) |
| `:saveas <file>` | Command | Save to new filename and switch buffer to it |
| `:x` | Command | Write (only if changed) and quit |
| `:qa` | Command | Quit all open buffers |
| `:qa!` | Command | Force quit all buffers, discard all changes |
| `vim +N <file>` | Shell | Open file and jump to line N |
| `vim -R <file>` | Shell | Open file in read-only mode |

---

## Chapter 3 — Moving Around

| Command | Mode | Description |
|---|---|---|
| `h` | Normal | Move left |
| `j` | Normal | Move down |
| `k` | Normal | Move up |
| `l` | Normal | Move right |
| `{n}h/j/k/l` | Normal | Move n times in that direction |
| `w` | Normal | Forward to start of next word |
| `W` | Normal | Forward to start of next WORD (whitespace-delimited) |
| `e` | Normal | Forward to end of current/next word |
| `E` | Normal | Forward to end of current/next WORD |
| `b` | Normal | Backward to start of current/previous word |
| `B` | Normal | Backward to start of current/previous WORD |
| `ge` | Normal | Backward to end of previous word |
| `0` | Normal | Start of line (column 1) |
| `^` | Normal | First non-whitespace character of line |
| `$` | Normal | End of line |
| `g_` | Normal | Last non-whitespace character of line |
| `f{char}` | Normal | Jump forward to next occurrence of char on line |
| `F{char}` | Normal | Jump backward to previous occurrence of char on line |
| `t{char}` | Normal | Jump forward to just before char on line |
| `T{char}` | Normal | Jump backward to just after char on line |
| `;` | Normal | Repeat last f/F/t/T in same direction |
| `,` | Normal | Repeat last f/F/t/T in opposite direction |
| `)` | Normal | Forward to start of next sentence |
| `(` | Normal | Backward to start of current/previous sentence |
| `}` | Normal | Forward to next paragraph |
| `{` | Normal | Backward to previous paragraph |
| `gg` | Normal | Jump to first line of file |
| `G` | Normal | Jump to last line of file |
| `{n}G` | Normal | Jump to line n |
| `:{n}` | Command | Jump to line n |
| `{n}%` | Normal | Jump to n% through the file |
| `Ctrl+F` | Normal | Scroll one full page forward |
| `Ctrl+B` | Normal | Scroll one full page backward |
| `Ctrl+D` | Normal | Scroll half page down |
| `Ctrl+U` | Normal | Scroll half page up |
| `Ctrl+E` | Normal | Scroll screen down one line (cursor stays) |
| `Ctrl+Y` | Normal | Scroll screen up one line (cursor stays) |
| `zz` | Normal | Centre current line on screen |
| `zt` | Normal | Scroll current line to top of screen |
| `zb` | Normal | Scroll current line to bottom of screen |
| `/{pattern}` | Normal | Search forward for pattern |
| `?{pattern}` | Normal | Search backward for pattern |
| `n` | Normal | Next search match |
| `N` | Normal | Previous search match |
| `*` | Normal | Search forward for exact word under cursor |
| `#` | Normal | Search backward for exact word under cursor |
| `g*` | Normal | Search forward, partial word match |
| `g#` | Normal | Search backward, partial word match |
| `:noh` | Command | Clear search highlights |
| `:set hlsearch` | Command | Highlight all search matches |
| `:set incsearch` | Command | Incremental search (move cursor as you type) |
| `:set ignorecase` | Command | Case-insensitive search |
| `:set smartcase` | Command | Case-sensitive only when pattern has uppercase |

---

## Chapter 4 — Changing Text

| Command | Mode | Description |
|---|---|---|
| `d{motion}` | Normal | Delete text covered by motion (e.g. `dw`, `d$`, `dG`) |
| `dd` | Normal | Delete entire current line |
| `{n}dd` | Normal | Delete n lines |
| `D` | Normal | Delete from cursor to end of line |
| `x` | Normal | Delete character under cursor |
| `X` | Normal | Delete character before cursor |
| `xp` | Normal | Swap current character with next (fix transposition) |
| `u` | Normal | Undo last change |
| `U` | Normal | Undo all changes to current line |
| `Ctrl+R` | Normal | Redo |
| `y{motion}` | Normal | Yank (copy) text covered by motion |
| `yy` | Normal | Yank entire current line |
| `{n}yy` | Normal | Yank n lines |
| `p` | Normal | Put (paste) after cursor / below current line |
| `P` | Normal | Put before cursor / above current line |
| `gp` | Normal | Put after cursor, move cursor to end of paste |
| `gP` | Normal | Put before cursor, move cursor to end of paste |
| `c{motion}` | Normal | Change (delete + enter Insert mode) |
| `cc` | Normal | Change entire current line |
| `C` | Normal | Change from cursor to end of line |
| `s` | Normal | Substitute character under cursor + Insert mode |
| `S` | Normal | Substitute line + Insert mode |
| `ciw` / `caw` | Normal | Change inner / around word (text object) |
| `ci"` / `ca"` | Normal | Change inside / around double quotes |
| `ci(` / `ca(` | Normal | Change inside / around parentheses |
| `v` | Normal | Enter character-wise Visual mode |
| `V` | Normal | Enter line-wise Visual mode |
| `Ctrl+V` | Normal | Enter block-wise Visual mode |
| `"x{op}` | Normal | Use named register x (e.g. `"ayy`, `"ap`) |
| `"Ax{op}` | Normal | Append to named register x |
| `"0p` | Normal | Paste from yank register (safe after deletes) |
| `"+y` / `"+p` | Normal | Yank to / paste from system clipboard |
| `"_d{motion}` | Normal | Delete to black hole register (truly removes) |
| `:registers` | Command | Show contents of all registers |
| `:s/old/new` | Command | Replace first occurrence on current line |
| `:s/old/new/g` | Command | Replace all occurrences on current line |
| `:s/old/new/gc` | Command | Replace all on current line with confirmation |
| `:%s/old/new/g` | Command | Replace all occurrences in entire file |
| `:5,15s/old/new/g` | Command | Replace in line range |
| `:'<,'>s/old/new/g` | Command | Replace in Visual selection |
| `:set spell` | Command | Enable spell checking |
| `:set spelllang=en_gb` | Command | Set spell check language |
| `:set nospell` | Command | Disable spell checking |
| `]s` / `[s` | Normal | Jump to next / previous misspelled word |
| `z=` | Normal | Show spelling suggestions |
| `zg` / `zw` | Normal | Add word to personal dictionary / bad-word list |

---

## Chapter 5 — Marks

| Command | Mode | Description |
|---|---|---|
| `m{a-z}` | Normal | Set a local mark (current buffer) |
| `m{A-Z}` | Normal | Set a global mark (across files, persists) |
| `'{mark}` | Normal | Jump to start of line containing mark |
| `` `{mark} `` | Normal | Jump to exact position (line + column) of mark |
| `''` | Normal | Jump back to line of last large jump (toggle) |
| ` `` ` | Normal | Jump back to exact position before last large jump (toggle) |
| `` `. `` | Normal | Jump to exact position of last change |
| `'.` | Normal | Jump to line of last change |
| `` `[ `` and `` `] `` | Normal | Start and end of last changed or yanked text |
| `` `< `` and `` `> `` | Normal | Start and end of last Visual selection |
| `` `^ `` | Normal | Position where Insert mode was last exited |
| `:marks` | Command | List all current marks |
| `:marks {letters}` | Command | List specific marks |
| `:delmarks {letter}` | Command | Delete a named mark |
| `:delmarks!` | Command | Delete all lowercase marks in current buffer |
| `y\`{mark}` | Normal | Yank from cursor to exact position of mark |
| `d'{mark}` | Normal | Delete from cursor to line of mark (linewise) |
| `c\`{mark}` | Normal | Change from cursor to exact position of mark |
| `Ctrl+O` | Normal | Go to older (previous) position in jump list |
| `Ctrl+I` | Normal | Go to newer (next) position in jump list |
| `:jumps` | Command | Display the full jump history |

---

## Chapter 6 — Buffers, Windows, and Tabs

| Command | Mode | Description |
|---|---|---|
| `:ls` | Command | List all open buffers |
| `:bn` / `:bp` | Command | Next / previous buffer |
| `:bf` / `:bl` | Command | First / last buffer |
| `:b {n}` | Command | Switch to buffer number n |
| `:b {name}` | Command | Switch to buffer by name (partial match) |
| `Ctrl+^` | Normal | Toggle between current and alternate buffer |
| `:bd` | Command | Delete (unload) current buffer |
| `:bd!` | Command | Force-delete buffer with unsaved changes |
| `:bw` | Command | Wipe buffer (remove from list entirely) |
| `%bd` | Command | Delete all buffers |
| `:e!` | Command | Reload file from disk, discard changes |
| `:e .` | Command | Open directory browser (netrw) |
| `:split` / `:sp` | Command | Horizontal split |
| `:vsplit` / `:vsp` | Command | Vertical split |
| `Ctrl+W s` | Normal | Horizontal split (shortcut) |
| `Ctrl+W v` | Normal | Vertical split (shortcut) |
| `Ctrl+W n` | Normal | New empty horizontal split |
| `:sb {n}` | Command | Open buffer n in horizontal split |
| `:vert sb {n}` | Command | Open buffer n in vertical split |
| `Ctrl+W w` / `W` | Normal | Cycle to next / previous window |
| `Ctrl+W h/j/k/l` | Normal | Move to window left/down/up/right |
| `Ctrl+W t` / `b` | Normal | Move to top-left / bottom-right window |
| `Ctrl+W =` | Normal | Equalise all window sizes |
| `Ctrl+W _` / `\|` | Normal | Maximise window height / width |
| `Ctrl+W +/-` | Normal | Increase / decrease window height |
| `Ctrl+W >/\<` | Normal | Increase / decrease window width |
| `:resize {n}` | Command | Set window height to n lines |
| `:vertical resize {n}` | Command | Set window width to n columns |
| `Ctrl+W c` | Normal | Close current window |
| `Ctrl+W o` / `:only` | Normal/Command | Close all other windows |
| `Ctrl+W r` / `R` | Normal | Rotate windows |
| `Ctrl+W x` | Normal | Exchange current window with next |
| `Ctrl+W H/J/K/L` | Normal | Move window to edge of screen |
| `:tabnew` | Command | Open new tab |
| `:tabclose` | Command | Close current tab |
| `gt` / `gT` | Normal | Next / previous tab |
| `{n}gt` | Normal | Go to tab n |
| `:tabs` | Command | List all tabs |
| `:r {file}` | Command | Insert file contents below current line |
| `:0r {file}` | Command | Insert at top of file |
| `:-1r {file}` | Command | Insert one line above cursor |
| `:{n}r {file}` | Command | Insert after line n |
| `:r! {cmd}` | Command | Insert output of shell command into buffer |
| `vimdiff f1 f2` | Shell | Open two files in diff mode |
| `:set scrollbind` | Command | Link scrolling of multiple windows |
| `]c` / `[c` | Normal | Jump to next / previous diff change |

---

## Chapter 7 — Configuration

| Command | Mode | Description |
|---|---|---|
| `:source ~/.vimrc` | Command | Reload the vimrc without restarting Vim |
| `:so %` | Command | Source the current file (when editing vimrc) |
| `:set` | Command | Show all non-default settings currently active |
| `:set all` | Command | Show every setting with its current value |
| `:set {option}?` | Command | Query the current value of an option |
| `:set no{option}` | Command | Disable a boolean option |
| `:set {option}!` | Command | Toggle a boolean option on/off |
| `set nocompatible` | vimrc | Disable Vi compatibility — always first line |
| `set number` / `nonu` | Command/vimrc | Show / hide line numbers |
| `set relativenumber` | vimrc | Show relative line numbers |
| `set cursorline` | vimrc | Highlight the current line |
| `set showmatch` | vimrc | Highlight matching brackets |
| `set showcmd` | vimrc | Show partial commands in status bar |
| `set hidden` | vimrc | Allow switching buffers without saving first |
| `set wildmenu` | vimrc | Enhanced Tab-completion menu |
| `set tabstop=4` | vimrc | Tab display width |
| `set shiftwidth=4` | vimrc | Indent width for `>>` and `<<` |
| `set expandtab` | vimrc | Insert spaces instead of tab characters |
| `set clipboard=unnamedplus` | vimrc | Use system clipboard by default |
| `set history=1000` | vimrc | Increase command history size |
| `set undolevels=1000` | vimrc | Increase undo history depth |
| `set backspace=indent,eol,start` | vimrc | Make Backspace work as expected in Insert mode |
| `:help option-summary` | Command | Browse all available settings in built-in help |
| `:syntax on` / `off` | Command | Enable / disable syntax highlighting |
| `:colorscheme {name}` | Command | Switch to a colour scheme |
| `:colorscheme <Tab>` | Command | Browse all available colour schemes |
| `nnoremap {key} {action}` | vimrc | Map a key in Normal mode (non-recursive) |
| `inoremap {key} {action}` | vimrc | Map a key in Insert mode |
| `vnoremap {key} {action}` | vimrc | Map a key in Visual mode |
| `let mapleader = ","` | vimrc | Set the leader key |
| `:ab {trigger} {expansion}` | Command | Define an abbreviation interactively |
| `iabbrev {trigger} {expansion}` | vimrc | Define an abbreviation in vimrc |
| `Ctrl+V` (in Insert) | Insert | Prevent next character from triggering abbreviation |
| `:command! {Name} {action}` | Command | Define a custom Command-mode command |
| `:! {shell-command}` | Command | Run a shell command from within Vim |
| `%` (in `:!` commands) | Command | Expands to the current filename |
| `:help` | Command | Open the help index |
| `:help {topic}` | Command | Open help for a specific topic |
| `Ctrl+]` (in help) | Normal | Follow a help hyperlink |

---

## Chapter 8 — Day to Day Vim

| Command | Mode | Description |
|---|---|---|
| `vim +{n} file` | Shell | Open file at line n |
| `vim +/{pattern} file` | Shell | Open file and jump to first match of pattern |
| `vim +"{cmd}" file` | Shell | Run a command after opening the file |
| `vim -d file1 file2` | Shell | Open two files in diff mode |
| `vim -R file` | Shell | Open file in read-only mode |
| `vim -u NONE file` | Shell | Open without loading vimrc |
| `vimdiff file1 file2` | Shell | Alias for vim -d |
| `:diffsplit {file}` | Command | Open file in diff mode (horizontal split) |
| `:vert diffsplit {file}` | Command | Open file in diff mode (vertical split) |
| `]c` | Normal | Jump to next difference |
| `[c` | Normal | Jump to previous difference |
| `do` | Normal | Diff Obtain — pull change from other file into this one |
| `dp` | Normal | Diff Put — push change from this file into the other |
| `:diffupdate` | Command | Recalculate and refresh diff highlights |
| `set diffopt+=vertical` | vimrc | Always use vertical (side-by-side) diff layout |
| `set diffopt+=iwhite` | vimrc | Ignore whitespace differences in diff |
| `vim {file}.zip` | Shell | Browse and edit files inside a ZIP archive |
| `vim {file}.tar.gz` | Shell | Browse and edit files inside a TAR archive |
| `gf` | Normal | Open file whose name is under the cursor |
| `gF` | Normal | Open file under cursor at the line number under cursor |
| `Ctrl+W f` | Normal | Open file under cursor in a new horizontal split |
| `Ctrl+W gf` | Normal | Open file under cursor in a new tab |
| `:set path+=src/**` | Command | Add directories to search path for gf |
| `:! {cmd}` | Command | Run a shell command; show output in overlay |
| `:r ! {cmd}` | Command | Insert shell command output below cursor |
| `:0r ! {cmd}` | Command | Insert shell command output at top of file |
| `:%! {cmd}` | Command | Filter entire buffer through external command |
| `:{range}! {cmd}` | Command | Filter a line range through external command |
| `'<,'>! {cmd}` | Visual | Filter Visual selection through external command |
| `:%!jq .` | Command | Format JSON with jq |
| `:%!sort` | Command | Sort all lines alphabetically |
| `:%!xxd` | Command | Convert buffer to hex dump |
| `:%!xxd -r` | Command | Convert hex dump back to binary |
