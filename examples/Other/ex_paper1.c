#include "assert.h"
int nondet();
void tick(int c);

void p1(int x,int y,int z){
assert(x>0);
assert(y>0);  
 while(x>0) {
   x--;
   y++;
 while(y>0 && nondet()){
     y--;   
     tick(2);
   }
 }
  while(y>0){
    y--; 
    for(int i=0;i<z;i++)
       tick(1);
  }
   
}