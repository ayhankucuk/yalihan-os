#!/usr/bin/perl
# timeout.pl — macOS-compatible process timeout wrapper
# Usage: perl timeout.pl <seconds> <cmd...>
# Exits: 42 if timeout, otherwise the command's exit code

use POSIX qw(setpgid SIGALRM WNOHANG);
use Time::HiRes qw(usleep);

die "Usage: $0 <timeout_secs> <cmd...>\n" unless @ARGV >= 2;

my $timeout = shift;
my $pid = fork;
die "fork failed: $!" unless defined $pid;

if ($pid == 0) {
    setpgid(0, 0);
    exec @ARGV;
    exit 127;
}

my $timed_out = 0;
local $SIG{ALRM} = sub {
    $timed_out = 1;
    kill TERM => -$pid;
};
alarm $timeout;
while (waitpid($pid, WNOHANG) == 0) {
    usleep 100_000;
}
alarm 0;
if ($timed_out) {
    waitpid($pid, 0);
    exit 42;
}
exit ($? >> 8);
