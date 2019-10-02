#include <windows.h>
#include <stdio.h>

int main(int argc, char *argv[])
{
	CONSOLE_SCREEN_BUFFER_INFO csbi;
	GetConsoleScreenBufferInfo(GetStdHandle(STD_ERROR_HANDLE), &csbi);
	printf("%d;%d", csbi.srWindow.Right - csbi.srWindow.Left + 1, csbi.srWindow.Bottom - csbi.srWindow.Top + 1);
	return 0;
}
