
#include <fstream>
#include <stdlib.h>
#include <stdio.h>
#include <iostream>
#include <netdb.h>
#include <unistd.h>
#include <sstream>
#include <string.h>
#include <math.h>
#include <fcntl.h>
#include <pthread.h>
#include <netinet/in.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <time.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <errno.h>
#include <cstring>

#define PORT    49500	// TODO: konfigurierbar machen / command line
#define MAXMSG  512

#define MAX_CONNECTIONS 100
#define CONNECTION_TIMEOUT 60

// In seconds. 21600 = 6 hours
#define CSVRELOADINTERVAL 21600

#include <unordered_set>

using namespace std;

